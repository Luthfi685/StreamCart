import os
import math
import collections
import uvicorn
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv
import mysql.connector

# Load Laravel's .env file
dotenv_path = os.path.join(os.path.dirname(__file__), '..', 'BACKEND', '.env')
load_dotenv(dotenv_path)

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = os.getenv("DB_PORT", "3306")
DB_DATABASE = os.getenv("DB_DATABASE", "streamcart")
DB_USERNAME = os.getenv("DB_USERNAME", "root")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")

app = FastAPI(title="StreamCart AI API", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

def get_db_connection():
    try:
        conn = mysql.connector.connect(
            host=DB_HOST,
            port=DB_PORT,
            database=DB_DATABASE,
            user=DB_USERNAME,
            password=DB_PASSWORD
        )
        return conn
    except mysql.connector.Error as err:
        print(f"Error connecting to database: {err}")
        return None

def compute_tfidf(documents):
    # tokenization
    docs_tokens = []
    for doc in documents:
        tokens = str(doc).lower().split()
        docs_tokens.append(tokens)
        
    N = len(docs_tokens)
    
    # Compute DF
    df = collections.defaultdict(int)
    for tokens in docs_tokens:
        unique_tokens = set(tokens)
        for t in unique_tokens:
            df[t] += 1
            
    # Compute IDF
    idf = {}
    for t, count in df.items():
        idf[t] = math.log(N / (1 + count))
        
    # Compute TF-IDF vectors
    tfidf_vectors = []
    for tokens in docs_tokens:
        tf = collections.Counter(tokens)
        vector = {}
        for t, count in tf.items():
            vector[t] = (count / len(tokens)) * idf[t]
        tfidf_vectors.append(vector)
        
    return tfidf_vectors

def cosine_similarity(vec1, vec2):
    intersection = set(vec1.keys()) & set(vec2.keys())
    numerator = sum([vec1[x] * vec2[x] for x in intersection])

    sum1 = sum([vec1[x]**2 for x in vec1.keys()])
    sum2 = sum([vec2[x]**2 for x in vec2.keys()])
    denominator = math.sqrt(sum1) * math.sqrt(sum2)

    if not denominator:
        return 0.0
    else:
        return float(numerator) / denominator

@app.get("/")
def read_root():
    return {"message": "StreamCart AI API is running"}

@app.get("/recommendations/{buyer_id}")
def get_recommendations(buyer_id: int, limit: int = 5):
    conn = get_db_connection()
    if not conn:
        raise HTTPException(status_code=500, detail="Database connection failed")
    
    try:
        cursor = conn.cursor(dictionary=True)
        # 1. Fetch all active products
        cursor.execute("SELECT id, name, description, category FROM products WHERE is_active = 1")
        products = cursor.fetchall()
        
        if not products:
            return {"recommended_product_ids": []}

        # 2. Fetch buyer's purchase history
        query_history = f"""
            SELECT DISTINCT oi.product_id 
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.buyer_id = {buyer_id} AND o.status IN ('processed', 'completed')
        """
        cursor.execute(query_history)
        history = cursor.fetchall()
        buyer_product_ids = [row['product_id'] for row in history]

        # 3. Handle Cold Start (No purchase history)
        if not buyer_product_ids:
            query_popular = f"""
                SELECT p.id
                FROM products p
                LEFT JOIN order_items oi ON p.id = oi.product_id
                WHERE p.is_active = 1
                GROUP BY p.id
                ORDER BY SUM(oi.quantity) DESC
                LIMIT {limit}
            """
            cursor.execute(query_popular)
            popular_ids = [row['id'] for row in cursor.fetchall()]
            return {"recommended_product_ids": popular_ids, "method": "popular"}

        # Combine features
        documents = []
        product_list = []
        for p in products:
            features = f"{p['category'] or ''} {p['name'] or ''} {p['description'] or ''}"
            documents.append(features)
            product_list.append(p['id'])

        # 4. Content-Based Filtering
        tfidf_vectors = compute_tfidf(documents)

        # Get indices of bought products
        bought_indices = [i for i, pid in enumerate(product_list) if pid in buyer_product_ids]

        if not bought_indices:
            return {"recommended_product_ids": product_list[:limit], "method": "fallback"}

        # Calculate average similarity score for all bought products
        sim_scores = []
        for i in range(len(product_list)):
            if product_list[i] in buyer_product_ids:
                sim_scores.append((i, -1.0)) # Don't recommend already bought items
                continue
                
            total_score = 0
            for b_idx in bought_indices:
                total_score += cosine_similarity(tfidf_vectors[i], tfidf_vectors[b_idx])
            avg_score = total_score / len(bought_indices)
            sim_scores.append((i, avg_score))

        # Sort by score descending
        sim_scores.sort(key=lambda x: x[1], reverse=True)

        recommended_ids = []
        for idx, score in sim_scores:
            if score >= 0:
                recommended_ids.append(product_list[idx])
            if len(recommended_ids) >= limit:
                break

        return {"recommended_product_ids": recommended_ids, "method": "content-based"}

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        cursor.close()
        conn.close()

if __name__ == "__main__":
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)
