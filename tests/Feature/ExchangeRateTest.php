<?php

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('Superadmin');
});

test('exchange rates master page can be rendered', function () {
    $currency = Currency::create([
        'code' => 'USD',
        'name' => 'United States Dollar',
    ]);

    ExchangeRate::create([
        'currency_id' => $currency->id,
        'currency_code' => 'USD',
        'date' => '2026-09-07',
        'unit' => 1.00,
        'rate_buy' => 17547.82,
        'rate_sell' => 17724.18,
        'rate_middle' => 17636.00,
        'source' => 'BI Kurs Transaksi',
    ]);

    $response = $this->actingAs($this->user)->get(route('exchange-rates.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ExchangeRates/Index')
        ->has('rates.data', 1)
        ->where('rates.data.0.currency_code', 'USD')
        ->where('rates.data.0.rate_middle', '17636.0000')
    );
});

test('filtering exchange rates by specific date returns matching records', function () {
    $currency = Currency::create([
        'code' => 'USD',
        'name' => 'United States Dollar',
    ]);

    ExchangeRate::create([
        'currency_id' => $currency->id,
        'currency_code' => 'USD',
        'date' => '2026-09-07',
        'unit' => 1.00,
        'rate_buy' => 17547.82,
        'rate_sell' => 17724.18,
        'rate_middle' => 17636.00,
    ]);

    ExchangeRate::create([
        'currency_id' => $currency->id,
        'currency_code' => 'USD',
        'date' => '2026-09-04',
        'unit' => 1.00,
        'rate_buy' => 17600.56,
        'rate_sell' => 17777.44,
        'rate_middle' => 17689.00,
    ]);

    $response = $this->actingAs($this->user)->get(route('exchange-rates.index', ['date' => '2026-09-07']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ExchangeRates/Index')
        ->has('rates.data', 1)
        ->where('rates.data.0.date', '2026-09-07')
    );
});

test('new exchange rate can be created manually and computes middle rate', function () {
    $currency = Currency::create([
        'code' => 'JPY',
        'name' => 'Japanese Yen',
    ]);

    $response = $this->actingAs($this->user)->post(route('exchange-rates.store'), [
        'currency_id' => $currency->id,
        'date' => '2026-09-07',
        'unit' => 100,
        'rate_buy' => 11225.58,
        'rate_sell' => 11341.30,
        'source' => 'Manual',
    ]);

    $response->assertRedirect(route('exchange-rates.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('exchange_rates', [
        'currency_code' => 'JPY',
        'date' => '2026-09-07',
        'rate_middle' => 11283.44, // (11225.58 + 11341.30) / 2
        'created_by' => $this->user->id,
    ]);
});

test('duplicate exchange rate for same currency and date is rejected', function () {
    $currency = Currency::create([
        'code' => 'EUR',
        'name' => 'Euro',
    ]);

    ExchangeRate::create([
        'currency_id' => $currency->id,
        'currency_code' => 'EUR',
        'date' => '2026-09-07',
        'unit' => 1,
        'rate_buy' => 20000,
        'rate_sell' => 20200,
        'rate_middle' => 20100,
    ]);

    $response = $this->actingAs($this->user)->post(route('exchange-rates.store'), [
        'currency_id' => $currency->id,
        'date' => '2026-09-07',
        'unit' => 1,
        'rate_buy' => 20050,
        'rate_sell' => 20250,
    ]);

    $response->assertSessionHasErrors(['date']);
});

test('exchange rate can be updated', function () {
    $currency = Currency::create([
        'code' => 'SGD',
        'name' => 'Singapore Dollar',
    ]);

    $rate = ExchangeRate::create([
        'currency_id' => $currency->id,
        'currency_code' => 'SGD',
        'date' => '2026-09-07',
        'unit' => 1,
        'rate_buy' => 13800,
        'rate_sell' => 13900,
        'rate_middle' => 13850,
    ]);

    $response = $this->actingAs($this->user)->put(route('exchange-rates.update', $rate->id), [
        'unit' => 1,
        'rate_buy' => 13850,
        'rate_sell' => 13950,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $rate->refresh();
    expect((float) $rate->rate_buy)->toBe(13850.0);
    expect((float) $rate->rate_sell)->toBe(13950.0);
    expect((float) $rate->rate_middle)->toBe(13900.0);
});

test('exchange rate can be soft deleted and restored from recycle bin', function () {
    $currency = Currency::create([
        'code' => 'GBP',
        'name' => 'British Pound',
    ]);

    $rate = ExchangeRate::create([
        'currency_id' => $currency->id,
        'currency_code' => 'GBP',
        'date' => '2026-09-07',
        'unit' => 1,
        'rate_buy' => 23700,
        'rate_sell' => 23900,
        'rate_middle' => 23800,
    ]);

    $response = $this->actingAs($this->user)->delete(route('exchange-rates.destroy', $rate->id));
    $response->assertRedirect();
    $this->assertSoftDeleted('exchange_rates', ['id' => $rate->id]);

    // Check recycle bin
    $resBin = $this->actingAs($this->user)->get(route('recycle-bin.index', ['type' => 'exchange_rates']));
    $resBin->assertOk();

    // Restore
    $resRestore = $this->actingAs($this->user)->post(route('recycle-bin.restore', [
        'type' => 'exchange_rates',
        'id' => $rate->id,
    ]));
    $resRestore->assertRedirect();
    $this->assertNotSoftDeleted('exchange_rates', ['id' => $rate->id]);

    // Force delete
    $rate->delete();
    $resForce = $this->actingAs($this->user)->delete(route('recycle-bin.force-delete', [
        'type' => 'exchange_rates',
        'id' => $rate->id,
    ]));
    $resForce->assertRedirect();
    $this->assertDatabaseMissing('exchange_rates', ['id' => $rate->id]);
});

test('sync endpoint triggers Bank Indonesia service', function () {
    $currency = Currency::create([
        'code' => 'USD',
        'name' => 'United States Dollar',
    ]);

    $fakeXml = '<?xml version="1.0" encoding="utf-8"?>
<DataSet xmlns="http://tempuri.org/">
  <diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">
    <NewDataSet xmlns="">
      <Table diffgr:id="Table1" msdata:rowOrder="0">
        <id_subkurslokal>994035</id_subkurslokal>
        <lnk_subkurslokal>44</lnk_subkurslokal>
        <nil_subkurslokal>1.00</nil_subkurslokal>
        <beli_subkurslokal>17547.82</beli_subkurslokal>
        <jual_subkurslokal>17724.18</jual_subkurslokal>
        <tgl_subkurslokal>2026-09-07T00:00:00+07:00</tgl_subkurslokal>
        <mts_subkurslokal>USD</mts_subkurslokal>
      </Table>
    </NewDataSet>
  </diffgr:diffgram>
</DataSet>';

    Http::fake([
        'https://www.bi.go.id/*' => Http::response($fakeXml, 200),
    ]);

    $response = $this->actingAs($this->user)->post(route('exchange-rates.sync'), [
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-07',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('exchange_rates', [
        'currency_code' => 'USD',
        'date' => '2026-09-07',
        'rate_buy' => 17547.82,
        'rate_sell' => 17724.18,
        'rate_middle' => 17636.00,
        'source' => 'BI Kurs Transaksi',
    ]);
});

test('navbar_exchange_rates is shared in Inertia props for authenticated users', function () {
    $usd = Currency::create([
        'code' => 'USD',
        'name' => 'United States Dollar',
    ]);

    $jpy = Currency::create([
        'code' => 'JPY',
        'name' => 'Japanese Yen',
    ]);

    ExchangeRate::create([
        'currency_id' => $usd->id,
        'currency_code' => 'USD',
        'date' => '2026-09-20',
        'unit' => 1.00,
        'rate_buy' => 16000.00,
        'rate_sell' => 16200.00,
        'rate_middle' => 16100.00,
    ]);

    ExchangeRate::create([
        'currency_id' => $jpy->id,
        'currency_code' => 'JPY',
        'date' => '2026-09-20',
        'unit' => 100.00,
        'rate_buy' => 11000.00,
        'rate_sell' => 11200.00,
        'rate_middle' => 11100.00,
    ]);

    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('navbar_exchange_rates', 2)
        ->where('navbar_exchange_rates.0.currency_code', 'JPY')
        ->where('navbar_exchange_rates.0.unit', 100)
        ->where('navbar_exchange_rates.0.rate_middle', 11100)
        ->where('navbar_exchange_rates.1.currency_code', 'USD')
        ->where('navbar_exchange_rates.1.unit', 1)
        ->where('navbar_exchange_rates.1.rate_middle', 16100)
    );
});

test('manual sync endpoint returns JSON response when requested via ajax', function () {
    Currency::create([
        'code' => 'USD',
        'name' => 'United States Dollar',
    ]);

    $fakeXml = '<?xml version="1.0" encoding="utf-8"?>
<DataSet xmlns="http://tempuri.org/">
  <diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">
    <NewDataSet xmlns="">
      <Table diffgr:id="Table1" msdata:rowOrder="0">
        <id_subkurslokal>994036</id_subkurslokal>
        <nil_subkurslokal>1.00</nil_subkurslokal>
        <beli_subkurslokal>16000.00</beli_subkurslokal>
        <jual_subkurslokal>16200.00</jual_subkurslokal>
        <tgl_subkurslokal>2026-09-21T00:00:00+07:00</tgl_subkurslokal>
        <mts_subkurslokal>USD</mts_subkurslokal>
      </Table>
    </NewDataSet>
  </diffgr:diffgram>
</DataSet>';

    Http::fake([
        'https://www.bi.go.id/*' => Http::response($fakeXml, 200),
    ]);

    $response = $this->actingAs($this->user)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('exchange-rates.sync'), [
            'currency_codes' => ['USD'],
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});
