import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LiveRoomPage } from './live-room.page';

describe('LiveRoomPage', () => {
  let component: LiveRoomPage;
  let fixture: ComponentFixture<LiveRoomPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(LiveRoomPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
