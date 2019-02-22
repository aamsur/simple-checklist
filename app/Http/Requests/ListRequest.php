<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;

class CreateRequest extends Request
{
    
    /**
     * Rules function
     *
     * @return array
     */
    public function rules()
    {
        $rules = array(
            'code'                => 'required|api-unique:documents,code',
            'terminal'            => 'foreign_key|api_exists:terminal-sales,id',
            'shipping_address'    => 'string',
            'customer_refference' => 'string',
            'notes'               => 'string',
            'tax'                 => 'numeric',
            'discount'            => 'numeric',
            'total_charge'        => 'required|numeric',
            'ordered_at'          => 'required|date_format:Y-m-d\TH:i:sP',
            'eta_at'              => 'required|date_format:Y-m-d\TH:i:sP',
            'created_by'          => 'foreign_key|api_exists:users,id',
            'customer'            => 'required',
        );
        
        if ($this->input('customer') != false) {
            $rules ['customer'] = 'foreign_key|api_exists:companies,id';
        }
        
        if ($this->input('items') && is_array($this->input('items'))) {
            foreach ($this->input('items') as $key => $item) {
                $rules['items.' . $key . '.item_variant'] = 'foreign_key|api_exists:item-variants,id';
                $rules['items.' . $key . '.pricing_type'] = 'foreign_key|api_exists:terminal-pricing-types,id';
                $rules['items.' . $key . '.quantity']     = 'required|numeric';
                $rules['items.' . $key . '.unit_prices']  = 'required|numeric|min:1';
                $rules['items.' . $key . '.unit_cost']    = 'numeric';
                $rules['items.' . $key . '.discount']     = 'numeric';
                $rules['items.' . $key . '.subtotal']     = 'numeric';
                $rules['items.' . $key . '.notes']        = 'string';
            }
        } else {
            $rules ['items'] = 'required';
        }
        
        return $rules;
    }
    
    /**
     * Sanitize the input.
     *
     * @return array
     */
    protected function sanitizeInput()
    {
        $input                      = $this->except('_token');
        $salesOrderResources        = app('resource.sales.orders');
        $input['code']              = generate_document_code($salesOrderResources);
        $komisiResource             = app(KomisiResource::class);
        $companiesResource          = app(CompaniesResource::class);
        $salesInvoiceOrdersResource = app(SalesInvoiceOrdersResource::class);
        $input['code_komisi']       = generate_document_code_alt($komisiResource, 'KM-%Y%m');
        $is_onhold                  = 0;
        if ($this->input('customer') != "") {
            $customer                 = $companiesResource->where(['id' => decrypt($this->input('customer'))])->first();
            $sales_orders             = $salesOrderResources->where(['sales.customer.id' => decrypt($this->input('customer')), 'document.status' => 'pending', 'document_type' => 'product'])->get();
            $total_charge_sales_order = $sales_orders ? $sales_orders->sum('sales.total_charge') : 0;
            $sales_invoices           = $salesInvoiceOrdersResource->where(['sales_invoice.sales.customer.id' => decrypt($this->input('customer')), 'sales_order.document_type' => 'product', 'Ex.sales_invoice.document.status.in' => 'finished.canceled',])->get();
            $total_sales_invoice      = $sales_invoices ? $sales_invoices->count() : 0;
            if ($customer->is_onebill) {
                if ($total_sales_invoice > 0) {
                    $is_onhold = 1;
                } else {
                    $is_onhold = 0;
                }
            } else {
                if (($customer->sisa_plafon - ($total_charge_sales_order + $this->input('total_charge'))) < 0) {
                    $is_onhold = 1;
                } else {
                    $is_onhold = 0;
                }
            }
        }
        
        $input['customer']                 = ($this->input('customer')) ? input_fk(decrypt($this->input('customer'))) : null;
        $input['purchase_order_reference'] = ($this->input('purchase_order_reference')) ? $this->input('purchase_order_reference') : null;
        $input['purchase_order_date']      = ($this->input('purchase_order_date')) ? date_to_timestamp($this->input('purchase_order_date')) : current_time();
        $input['terminal']                 = ($this->input('terminal')) ? input_fk(decrypt($this->input('terminal'))) : null;
        $input['is_onhold']                = $is_onhold;
        $input['payment_received']         = ($this->input('payment_received')) ? 1 : 0;
        $input['auto_invoice']             = ($this->input('auto_invoice')) ? 1 : 0;
        $input['auto_fulfillment']         = ($this->input('auto_fulfillment')) ? 1 : 0;
        $input['ordered_at']               = ($this->input('ordered_at')) ? date_to_timestamp($this->input('ordered_at')) : current_time();
        $input['eta_at']                   = ($this->input('eta_at')) ? date_to_timestamp($this->input('eta_at')) : null;
        $input['is_paid']                  = ($this->input('is_paid')) ? 1 : 0;
        $input['is_taxable']               = ($this->input('is_taxable')) ? 1 : 0;
        $input['tax']                      = ($this->input('is_taxable')) ? (float) $this->input('tax') : 0;
        $input['discount']                 = (float) $this->input('discount');
        $input['discount_amount']          = (float) $this->input('discount_amount');
        $input['shipping_cost']            = (float) $this->input('shipping_cost');
        $input['total_charge']             = (float) $this->input('total_charge');
        $input['potongan_komisi']          = $this->input('potongan_komisi');
        $input['komisi']                   = $this->input('komisi');
        $input['sales_agent']              = $this->input('sales_agent');
        $input ['items']                   = array();
        
        $total_cost              = 0;
        $total_price             = 0;
        $internal_total          = 0;
        $untaxed_discount_amount = 0;
        $document_status         = 'active';
        $fulfillment_status      = null;
        $invoices_status         = null;
        $payment_status          = null;
        $payment_status          = ($input['is_paid'] == 1) ? 'paid' : 'unpaid';
        
        if ($this->input('customer') == 'walkin') {
            $input['auto_invoice']     = 1;
            $input['auto_fulfillment'] = 1;
            $input['is_paid']          = 1;
            $input['is_onhold']        = 0;
            $total_paid                = $input['total_charge'];
            $document_status           = 'finished';
            $fulfillment_status        = 'delivered';
            $invoices_status           = 'invoiced';
            $payment_status            = 'paid';
        } else {
            $invoices_status    = ($input['auto_invoice'] == 1) ? 'invoiced' : null;
            $fulfillment_status = ($input['auto_fulfillment'] == 1) ? 'new' : null;
            $total_paid         = ($input['is_paid'] == 1) ? $input['total_charge'] : 0;
            
            if ($invoices_status == 'invoiced') {
                $payment_status = ($input['is_paid'] == 1) ? 'paid' : 'unpaid';
            }
        }
        
        if (is_array($this->input('items'))) {
            foreach ($this->input('items') as $key => $data) {
                $quantity  = (float) $data['external_quantity'] > (float) $data['quantity'] ? (float) $data['external_quantity'] : (float) $data['quantity'];
                $prices    = (float) $data['external_prices'] > (float) $data['unit_prices'] ? (float) $data['external_prices'] : (float) $data['unit_prices'];
                $discount  = isset($data['discount']) ? (float) $data['discount'] : 0;
                $unit_cost = isset($data['unit_cost']) ? (float) $data ['unit_cost'] : 0;
                
                if ($quantity > 0) {
                    $item = array(
                        'item_variant'      => isset($data['item_variant']) ? input_fk(decrypt($data ['item_variant'])) : null,
                        'pricing_type'      => isset($data['pricing_type']) ? input_fk(decrypt($data ['pricing_type'])) : null,
                        'quantity'          => (float) $data['quantity'],
                        'unit_prices'       => (float) $data['unit_prices'],
                        'unit_cost'         => $unit_cost,
                        'discount'          => $discount,
                        'subtotal'          => (float) calculate_subtotal((float) $prices, (float) $quantity, $discount),
                        'notes'             => isset($data['notes']) ? $data ['notes'] : null,
                        'external_quantity' => $quantity,
                        'external_prices'   => $prices,
                        
                        'untaxed_unit_prices'     => isset($data['untaxed_unit_prices']) && (int) $input['is_include_tax'] ? (float) $data['untaxed_unit_prices'] : (float) $data['unit_prices'],
                        'untaxed_external_prices' => isset($data['untaxed_external_prices']) && (int) $input['is_include_tax'] ? (float) $data['untaxed_external_prices'] : (float) $prices,
                        'untaxed_subtotal'        => isset($data['untaxed_subtotal']) && (int) $input['is_include_tax'] ? (float) $data['untaxed_subtotal'] : (float) calculate_subtotal((float) $prices, (float) $quantity, $discount),
                    );
                    
                    $subtotal_cost = $item['unit_cost'] * $item['quantity'];
                    $total_cost += $subtotal_cost;
                    
                    $subtotal_internal = ((float) $data['quantity'] * (float) $data['unit_prices']) - (((float) $data['quantity'] * (float) $data['unit_prices']) * $data['discount'] / 100);
                    $internal_total += $subtotal_internal;
                    
                    $total_price += $item['subtotal'];
                    
                    $input ['items'][] = $item;
                }
            }
        }
        
        $total_price = round($total_price, 2);
        
        $commission     = $total_price - $internal_total;
        $commission     = $commission > 0 ? $commission : 0;
        $sales_discount = ($total_price * $input['discount']) / 100;
        
        //tax dihitung setelah total price dikurang discount
        $tax_amount = (($total_price - $sales_discount) * $input['tax']) / 100;
        if ($input['discount'] > 0 && $input['discount_amount'] == 0) {
            $discount_amount = ($total_price * $input['discount']) / 100;
        } else {
            $discount_amount = $input['discount_amount'];
        }
        
        $input['_formated']['documents'] = array(
            'code'           => $input['code'],
            'status'         => ($input['is_onhold'] == 1) ? 'pending' : $document_status,
            'type'           => 'sales_orders',
            'internal_notes' => '',
            'reject_notes'   => '',
            'created_by'     => input_fk(current_user()->id),
        );
        $input['_formated']['sales']     = array(
            'discount_is_percentage'  => (int) $input['is_include_tax'] ? 1 : (int) $this->input('discount_is_percentage'),
            'customer'                => $input['customer'],
            'shipping_cost'           => $input['shipping_cost'],
            'discount'                => $input['discount'],
            'tax'                     => $input['tax'],
            'commission'              => $commission,
            'discount_amount'         => $discount_amount,
            'tax_amount'              => $tax_amount,
            'total_price'             => $total_price,
            'total_cost'              => $total_cost,
            'total_charge'            => $input['total_charge'],
            'total_tagihan'           => $input['total_charge'],
            'total_paid'              => ($input['is_onhold'] == 1) ? 0 : $total_paid,
            'payment_status'          => ($input['is_onhold'] == 1) ? null : $payment_status,
            'payment_received'        => $input['payment_received'],
            'is_taxable'              => $input['is_taxable'],
            'untaxed_total_price'     => isset($input['untaxed_total_price']) && (int) $input['is_include_tax'] ? (float) $input['untaxed_total_price'] : $total_price,
            'untaxed_commission'      => isset($input['untaxed_commission']) && (int) $input['is_include_tax'] ? (float) $input['untaxed_commission'] : $commission,
            'untaxed_discount_amount' => isset($input['untaxed_discount_amount']) && (int) $input['is_include_tax'] ? (float) $input['untaxed_discount_amount'] : $discount_amount,
            'untaxed_tax_amount'      => isset($input['untaxed_tax_amount']) && (int) $input['is_include_tax'] ? (float) $input['untaxed_tax_amount'] : $tax_amount,
        );
        
        $input['_formated']['sales_orders'] = array(
            'terminal'                 => $input['terminal'],
            'type'                     => 'sales',
            'document_type'            => 'product',
            'customer_refference'      => $input['customer_refference'],
            'purchase_order_reference' => $input['purchase_order_reference'],
            'purchase_order_date'      => $input['purchase_order_date'],
            'shipping_address'         => $input['shipping_address'],
            'customer_notes'           => $input['notes'],
            'is_dropship'              => 0,
            'auto_fulfillment'         => $input['auto_fulfillment'],
            'auto_invoice'             => $input['auto_invoice'],
            'ordered_at'               => $input['ordered_at'],
            'eta_at'                   => $input['eta_at'],
            'is_include_tax'           => (int) $input['is_include_tax'],
        );
        
        $potongan_komisi = $input['total_charge'] * $input['potongan_komisi'] / 100;
        $hasil           = $input['total_charge'] - $potongan_komisi;
        $hasil_komisi    = $hasil * $input['komisi'] / 100;
        
        if ($input['sales_agent'] == "") {
            
        } else {
            $input['_formated']['komisi'] = array(
                'code'        => $input['code_komisi'],
                'sales_agent' => input_fk($input['sales_agent']),
                'amount'      => $hasil_komisi,
                'created_at'  => $input['ordered_at'],
            );
        }
        
        $input['_formated']['sales_order_items'] = $input ['items'];
        
        $this->replace($input);
        
        return $this->all();
    }
    
}
