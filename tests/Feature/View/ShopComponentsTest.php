<?php

describe('price', function () {
    it('shows the MRP struck through when the price is lower', function () {
        $this->blade('<x-shop.price :paise="34900" :mrp="49900" />')
            ->assertSee('₹349')
            ->assertSeeInOrder(['MRP', '<s>₹499</s>'], false);
    });

    it('hides the MRP when it is not higher than the price', function () {
        $this->blade('<x-shop.price :paise="52000" :mrp="52000" />')
            ->assertSee('₹520')
            ->assertDontSee('<s>', false);
    });
});

describe('price tag', function () {
    it('shows the discount on an offer tag', function () {
        $this->blade('<x-shop.price-tag offer :paise="34900" :mrp="49900" />')
            ->assertSee('30% off');
    });

    it('renders no offer tag without a discount', function () {
        $this->blade('<x-shop.price-tag offer :paise="52000" :mrp="52000" />')
            ->assertDontSee('off');
    });
});

describe('product card', function () {
    it('marks out of stock products', function () {
        $this->blade('<x-shop.product-card name="Face gel" :paise="19950" :in-stock="false" />')
            ->assertSee('Out of stock');
    });

    it('uses the product name as the image description when there is no photo', function () {
        $this->blade('<x-shop.product-card name="Face gel" category="cosmetics" :paise="19950" />')
            ->assertSee('role="img" aria-label="Face gel"', false);
    });
});

describe('order tracker', function () {
    it('marks the current step for assistive technology', function () {
        $steps = [
            ['label' => 'Packed', 'state' => 'done'],
            ['label' => 'Out for delivery', 'state' => 'current'],
            ['label' => 'Delivered', 'state' => 'upcoming'],
        ];

        $this->blade('<x-shop.order-tracker :steps="$steps" />', ['steps' => $steps])
            ->assertSeeInOrder(['Completed:', 'Packed', 'aria-current="step"', 'Current step:', 'Out for delivery', 'Next:', 'Delivered'], false);
    });

    it('shows a failed current step in the danger colour', function () {
        $steps = [['label' => 'Delivery failed', 'state' => 'current']];

        $this->blade('<x-shop.order-tracker failed :steps="$steps" />', ['steps' => $steps])
            ->assertSee('bg-danger', false)
            ->assertDontSee('bg-brand', false);
    });
});

describe('delivery order card', function () {
    it('asks the delivery partner to collect cash on COD orders', function () {
        $this->blade('<x-shop.delivery-order-card order-number="ORD-1" customer="Aman" area="Kankarbagh" pincode="800020" :items="1" :cod-paise="64000" status="Assigned" />')
            ->assertSee('Collect')
            ->assertSee('₹640');
    });

    it('shows collected cash once the order is delivered', function () {
        $this->blade('<x-shop.delivery-order-card order-number="ORD-1" customer="Aman" area="Kankarbagh" pincode="800020" :items="2" :cod-paise="64000" collected status="Delivered" />')
            ->assertSee('Collected')
            ->assertSee('2 items');
    });

    it('shows prepaid orders as paid', function () {
        $this->blade('<x-shop.delivery-order-card order-number="ORD-1" customer="Aman" area="Kankarbagh" pincode="800020" :items="1" status="Assigned" />')
            ->assertSee('Paid by UPI')
            ->assertDontSee('Collect');
    });
});

describe('quantity stepper', function () {
    it('submits the quantity with a form when named', function () {
        $this->blade('<x-shop.quantity-stepper name="qty" :value="2" />')
            ->assertSee('<input type="hidden" name="qty"', false)
            ->assertSee('aria-label="Decrease quantity"', false);
    });
});

describe('logo', function () {
    it('shows the shop name and initials from settings', function () {
        config(['shop.name' => 'Sweet Corner', 'shop.logo_path' => null]);
        app()->forgetScopedInstances();

        $this->blade('<x-shop.logo />')
            ->assertSee('Sweet Corner')
            ->assertSee('SC');
    });

    it('shows the uploaded logo when there is one', function () {
        config(['shop.name' => 'Sweet Corner', 'shop.logo_path' => 'branding/logo.png']);
        app()->forgetScopedInstances();

        $this->blade('<x-shop.logo />')
            ->assertSee('branding/logo.png', false)
            ->assertSee('alt="Sweet Corner"', false);
    });
});
