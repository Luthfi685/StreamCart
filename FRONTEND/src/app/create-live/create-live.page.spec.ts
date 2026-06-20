import { ComponentFixture, TestBed } from '@angular/core/testing';
import { CreateLivePage } from './create-live.page';

describe('CreateLivePage', () => {
  let component: CreateLivePage;
  let fixture: ComponentFixture<CreateLivePage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(CreateLivePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
