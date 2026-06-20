import { Component, OnInit } from '@angular/core';
import { NavController, ToastController } from '@ionic/angular';
import { HttpClient } from '@angular/common/http';
import { ChangeDetectorRef } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { AddressService } from '../services/address.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-add-address',
  templateUrl: './add-address.page.html',
  styleUrls: ['./add-address.page.scss'],
  standalone: false
})
export class AddAddressPage implements OnInit {
  fullName: string = '';
  phoneCode: string = 'ID +62';
  phoneNumber: string = '';
  
  locationName: string = 'Jl. Perum Sirnabaya Indah';
  locationDesc: string = 'Puseurjaya, Telukjambe Timur, Kab. Karawan...';
  addressDetail: string = '';
  isDefault: boolean = false;

  // Realtime Modal Selection State
  isModalOpen = false;
  isLoadingRegions = false;
  selectionStep: 'province' | 'city' | 'district' = 'province';
  
  selectedProvince: any = null;
  selectedCity: any = null;
  selectedDistrict: any = null;
  selectedFullRegion: string = '';

  currentList: any[] = [];
  isLocating = false;

  constructor(
    private navCtrl: NavController,
    private toastController: ToastController,
    private http: HttpClient,
    private auth: AuthService,
    private addressService: AddressService,
    private cdr: ChangeDetectorRef
  ) { }

  ngOnInit() {
    this.auth.user$.subscribe(user => {
      if (user) {
        if (!this.fullName) this.fullName = user.name || '';
        if (!this.phoneNumber) this.phoneNumber = user.phone || '';
      }
    });
  }

  ionViewWillEnter() {
    this.loadSavedAddress();
  }

  loadSavedAddress() {
    this.addressService.getAddresses().subscribe({
      next: (res) => {
        if (res.data && res.data.length > 0) {
          const address = res.data[0]; // Load the default/first address for editing
          this.fullName = address.receiver_name;
          this.phoneNumber = address.phone;
          this.selectedFullRegion = address.region;
          this.locationName = address.location_name;
          this.addressDetail = address.address_detail || '';
          this.isDefault = address.is_default;
          this.locationDesc = '';
          this.cdr.detectChanges(); // Force UI update
        }
      },
      error: (err) => console.error('Gagal load alamat', err)
    });
  }

  goBack() {
    this.navCtrl.back();
  }

  openAddressModal() {
    this.isModalOpen = true;
    this.selectionStep = 'province';
    this.selectedProvince = null;
    this.selectedCity = null;
    this.selectedDistrict = null;
    this.fetchProvinces();
  }

  closeAddressModal() {
    this.isModalOpen = false;
  }

  stepBack() {
    if (this.selectionStep === 'district') {
      this.selectionStep = 'city';
      this.fetchCities(this.selectedProvince.id);
    } else if (this.selectionStep === 'city') {
      this.selectionStep = 'province';
      this.fetchProvinces();
    }
  }

  fetchProvinces() {
    this.isLoadingRegions = true;
    this.http.get(`${environment.apiUrl}/regions/provinces`).subscribe({
      next: (res: any) => {
        this.currentList = res;
        this.isLoadingRegions = false;
      },
      error: (err) => {
        console.error('Failed to load provinces', err);
        this.isLoadingRegions = false;
      }
    });
  }

  fetchCities(provinceId: string | number) {
    this.isLoadingRegions = true;
    this.http.get(`${environment.apiUrl}/regions/cities/${provinceId}`).subscribe({
      next: (res: any) => {
        this.currentList = res;
        this.isLoadingRegions = false;
      },
      error: (err) => {
        console.error('Failed to load cities', err);
        this.isLoadingRegions = false;
      }
    });
  }

  fetchDistricts(cityId: string | number) {
    this.isLoadingRegions = true;
    this.http.get(`${environment.apiUrl}/regions/districts/${cityId}`).subscribe({
      next: (res: any) => {
        this.currentList = res;
        this.isLoadingRegions = false;
      },
      error: (err) => {
        console.error('Failed to load districts', err);
        this.isLoadingRegions = false;
      }
    });
  }

  selectRegion(item: any) {
    if (this.selectionStep === 'province') {
      this.selectedProvince = item;
      this.selectionStep = 'city';
      this.fetchCities(item.id);
    } else if (this.selectionStep === 'city') {
      this.selectedCity = item;
      this.selectionStep = 'district';
      this.fetchDistricts(item.id);
    } else if (this.selectionStep === 'district') {
      this.selectedDistrict = item;
      this.selectedFullRegion = `${this.selectedProvince.name}, ${this.selectedCity.name}, ${this.selectedDistrict.name}`;
      this.closeAddressModal();
    }
  }

  useCurrentLocation() {
    this.isLocating = true;
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const lat = position.coords.latitude;
          const lon = position.coords.longitude;
          
          this.http.get(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`).subscribe({
            next: (res: any) => {
              const address = res.address;
              if (address) {
                const province = address.state || address.province || '';
                const city = address.city || address.town || address.county || '';
                const district = address.suburb || address.village || address.neighbourhood || '';
                
                this.locationName = address.road || 'Jalan Tidak Diketahui';
                this.locationDesc = [district, city, province].filter(Boolean).join(', ');
              } else {
                this.fallbackLocation();
              }
              this.isLocating = false;
            },
            error: (err) => {
              console.error('Reverse geocoding failed', err);
              this.fallbackLocation();
            }
          });
        },
        (error) => {
          console.error('Geolocation failed', error);
          this.fallbackLocation();
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    } else {
      this.fallbackLocation();
    }
  }

  fallbackLocation() {
    this.locationName = 'Gagal mengakses GPS';
    this.locationDesc = 'Pastikan izin lokasi browser Anda aktif dan terhubung ke internet.';
    this.isLocating = false;
  }

  async saveAddress() {
    if (!this.isFormValid) return;

    // Region from dropdown or fallback to location string
    const regionText = this.selectedFullRegion ? this.selectedFullRegion : (this.locationDesc ? this.locationDesc : 'Wilayah Tidak Diketahui');
    
    // Final location name (include description if exists)
    const finalLocationName = this.locationDesc 
      ? `${this.locationName}, ${this.locationDesc}` 
      : this.locationName;

    const payload = {
      receiver_name: this.fullName,
      phone: this.phoneNumber,
      region: regionText,
      location_name: finalLocationName,
      address_detail: this.addressDetail,
      is_default: this.isDefault
    };
    
    this.addressService.saveAddress(payload).subscribe({
      next: async () => {
        const toast = await this.toastController.create({
          message: 'Alamat berhasil disimpan!',
          duration: 2000,
          color: 'success',
          position: 'bottom'
        });
        toast.present();

        this.navCtrl.back();
      },
      error: async (err) => {
        console.error('Save Address Error:', err);
        const msg = err.error?.message || err.message || 'Gagal menyimpan alamat.';
        const toast = await this.toastController.create({
          message: `Gagal: ${msg}`,
          duration: 3000,
          color: 'danger',
          position: 'bottom'
        });
        toast.present();
      }
    });
  }

  get isFormValid(): boolean {
    return this.fullName.trim() !== '' && this.phoneNumber.trim() !== '';
  }
}
