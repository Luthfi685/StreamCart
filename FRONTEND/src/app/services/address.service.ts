import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface UserAddress {
  id?: number;
  receiver_name: string;
  phone: string;
  region: string;
  location_name: string;
  address_detail?: string;
  is_default: boolean;
}

@Injectable({
  providedIn: 'root'
})
export class AddressService {
  private apiUrl = `${environment.apiUrl}/addresses`;

  constructor(private http: HttpClient) {}

  getAddresses(): Observable<any> {
    return this.http.get(this.apiUrl);
  }

  saveAddress(data: UserAddress): Observable<any> {
    return this.http.post(this.apiUrl, data);
  }

  deleteAddress(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }
}
