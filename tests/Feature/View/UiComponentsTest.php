<?php

use Illuminate\View\ViewException;

describe('button', function () {
    it('renders a link when given an href', function () {
        $this->blade('<x-ui.button href="/cart">View bag</x-ui.button>')
            ->assertSee('<a href="/cart"', false)
            ->assertDontSee('<button', false);
    });

    it('renders a button of the given type', function () {
        $this->blade('<x-ui.button type="submit">Place order</x-ui.button>')
            ->assertSee('<button', false)
            ->assertSee('type="submit"', false);
    });

    it('disables itself while its Livewire action runs', function () {
        $this->blade('<x-ui.button loading="placeOrder">Place order</x-ui.button>')
            ->assertSee('wire:loading.attr="disabled"', false)
            ->assertSee('wire:target="placeOrder"', false);
    });
});

describe('icon', function () {
    it('hides decorative icons from screen readers', function () {
        $this->blade('<x-ui.icon name="search" />')
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('<path', false);
    });

    it('labels meaningful icons', function () {
        $this->blade('<x-ui.icon name="truck" label="Out for delivery" />')
            ->assertSee('role="img" aria-label="Out for delivery"', false);
    });

    it('rejects icon names that are not in the icon set', function (string $name) {
        $this->blade('<x-ui.icon :name="$name" />', ['name' => $name]);
    })->with(['does-not-exist', '../../.env'])->throws(ViewException::class, 'Unknown icon');
});

describe('icon button', function () {
    it('includes the count in its accessible name', function () {
        $this->blade('<x-ui.icon-button icon="shopping-bag" label="Your bag" :count="3" />')
            ->assertSee('aria-label="Your bag (3)"', false);
    });

    it('caps the visible count at 99+', function () {
        $this->blade('<x-ui.icon-button icon="shopping-bag" label="Your bag" :count="120" />')
            ->assertSee('99+');
    });
});

describe('input', function () {
    it('links the hint to the field', function () {
        $this->blade('<x-ui.input label="Mobile number" name="phone" hint="We call this number." required />')
            ->assertSee('for="field-phone"', false)
            ->assertSee('aria-describedby="field-phone-hint"', false)
            ->assertSee('We call this number.')
            ->assertDontSee('(optional)');
    });

    it('marks fields that are not required as optional', function () {
        $this->blade('<x-ui.input label="Landmark" name="landmark" />')
            ->assertSee('(optional)');
    });

    it('shows the validation error from the error bag', function () {
        $this->withViewErrors(['pincode' => 'Enter a 6-digit pincode.'])
            ->blade('<x-ui.input label="Pincode" name="pincode" hint="Where we deliver." required />')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="field-pincode-error"', false)
            ->assertSee('Enter a 6-digit pincode.')
            ->assertDontSee('Where we deliver.');
    });

    it('escapes the submitted value', function () {
        $this->blade('<x-ui.input label="Name" name="name" :value="$value" />', ['value' => '"><script>alert(1)</script>'])
            ->assertDontSee('<script>alert(1)</script>', false);
    });
});

describe('select', function () {
    it('marks the selected option', function () {
        $this->blade('<x-ui.select label="State" name="state" :options="[\'BR\' => \'Bihar\', \'UP\' => \'Uttar Pradesh\']" selected="UP" required />')
            ->assertSee('<option value="UP" selected>Uttar Pradesh</option>', false)
            ->assertSee('<option value="BR" >Bihar</option>', false);
    });
});

describe('pagination', function () {
    it('shows the page position and disables previous on the first page', function () {
        $paginator = new Illuminate\Pagination\LengthAwarePaginator(range(1, 12), 30, 12, 1, ['path' => '/products']);

        $this->blade('<x-ui.pagination :paginator="$paginator" />', ['paginator' => $paginator])
            ->assertSee('Page 1 of 3')
            ->assertSee('disabled', false)
            ->assertSee('href="/products?page=2"', false);
    });

    it('renders nothing when there is a single page', function () {
        $paginator = new Illuminate\Pagination\LengthAwarePaginator(range(1, 5), 5, 12, 1);

        $this->blade('<x-ui.pagination :paginator="$paginator" />', ['paginator' => $paginator])
            ->assertDontSee('Pagination');
    });
});
