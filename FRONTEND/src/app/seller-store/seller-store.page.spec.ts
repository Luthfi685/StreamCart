import { ComponentFixture, TestBed } from '@angular/core/testing';
import { SellerStorePage } from './seller-store.page';

describe('SellerStorePage', () => {
  let component: SellerStorePage;
  let fixture: ComponentFixture<SellerStorePage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(SellerStorePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
