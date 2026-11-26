<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;

class PortalScheduleTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_monday_and_wednesday_operating_hours_allow_normal_portal(): void
    {
        foreach (['2026-11-16 10:00:00', '2026-11-18 14:00:00'] as $timestamp) {
            Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, 'Asia/Manila'));

            $response = $this->get('/portal');

            $response->assertStatus(200);
            $response->assertSee('Normal Operations: Mon/Wed 8:00 AM - 5:00 PM');
            $response->assertSee('Live Inventory Directory');
            $response->assertSee('id="requisitionForm"', false);
            $response->assertDontSee('emergencyConfirmModal', false);
        }
    }

    public function test_tuesday_thursday_friday_operating_hours_show_prf_only_and_block_ris(): void
    {
        foreach (['2026-11-17 10:00:00', '2026-11-19 10:00:00', '2026-11-20 10:00:00'] as $timestamp) {
            Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, 'Asia/Manila'));

            $response = $this->get('/portal');

            $response->assertStatus(200);
            $response->assertSee('PURCHASE REQUISITION FORM (PRF) ONLY');
            $response->assertSee('Open Purchase Requisition Form (PRF)');
            $response->assertDontSee('Live Inventory Directory');
            $response->assertDontSee('id="requisitionForm"', false);
            $response->assertDontSee('id="fabCartButton"', false);
            $response->assertDontSee('emergencyConfirmModal', false);

            $submitResponse = $this->post('/submit-request', [
                'department_name' => 'ICT',
                'requested_by' => 'Test Head',
                'purpose' => 'Emergency test',
                'is_emergency' => '1',
                'cart_data' => json_encode([['id' => 1, 'qty' => 1]]),
            ]);

            $submitResponse->assertRedirect(route('portal'));
            $submitResponse->assertSessionHas('danger');
        }

        Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s', '2026-11-17 10:00:00', 'Asia/Manila'));

        $prfResponse = $this->post('/portal/petty-cash/print', [
            'department_name' => 'ICT',
            'requested_by' => 'Test Head',
            'request_date' => '2026-11-17',
            'item_name' => ['SSD 500GB'],
            'quantity' => [1],
            'purpose' => 'Urgent replacement',
        ]);

        $prfResponse->assertStatus(200);
    }

    public function test_weekend_and_off_hours_close_portal_and_prf(): void
    {
        foreach (['2026-11-21 10:00:00', '2026-11-17 18:00:00'] as $timestamp) {
            Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, 'Asia/Manila'));

            $response = $this->get('/portal');

            $response->assertStatus(200);
            $response->assertSee('SYSTEM CLOSED');
            $response->assertDontSee('Live Inventory Directory');
            $response->assertDontSee('Open Purchase Requisition Form (PRF)');
            $response->assertDontSee('id="fabCartButton"', false);

            $submitResponse = $this->post('/submit-request', [
                'department_name' => 'ICT',
                'requested_by' => 'Test Head',
                'purpose' => 'Off hours test',
                'cart_data' => json_encode([['id' => 1, 'qty' => 1]]),
            ]);

            $submitResponse->assertRedirect(route('portal'));
            $submitResponse->assertSessionHas('danger');

            $prfResponse = $this->post('/portal/petty-cash/print', [
                'department_name' => 'ICT',
                'requested_by' => 'Test Head',
                'request_date' => '2026-11-21',
                'item_name' => ['SSD 500GB'],
                'quantity' => [1],
                'purpose' => 'Closed test',
            ]);

            $prfResponse->assertStatus(403);
        }
    }
}
