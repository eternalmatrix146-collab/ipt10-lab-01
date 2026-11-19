<?php

declare(strict_types=1);

namespace Database\Seeders;

use Phantom\Database\Connection;

/**
 * Demo data: categories, products, an admin, sample customers, reviews, and
 * three weeks of orders so the admin dashboard and the Intel agent have
 * something to analyse.
 *
 * Demo accounts (local test credentials only):
 *   admin@midnight.test  / phantom-admin   (admin)
 *   ana@midnight.test    / shopper123      (customer)
 */
class DatabaseSeeder
{
    public function __construct(private Connection $db)
    {
    }

    public function run(): void
    {
        mt_srand(1105);
        $now = date('Y-m-d H:i:s');

        $categories = [
            ['Masks & Accessories', 'masks-accessories', 'Disguises, gloves and finishing touches for a clean getaway.', 'mask'],
            ['Outerwear', 'outerwear', 'Coats and jackets cut for dramatic exits.', 'coat'],
            ['Gadgets', 'gadgets', 'Pocket tech for planners, lookouts and night owls.', 'gadget'],
            ['Books & Journals', 'books-journals', 'Notebooks, guides and novels for the long nights.', 'book'],
            ['Coffee & Snacks', 'coffee-snacks', 'Late-night fuel: beans, brews and bites.', 'cup'],
        ];
        $categoryIds = [];
        foreach ($categories as [$name, $slug, $description, $motif]) {
            $categoryIds[$slug] = $this->db->table('categories')->insert([
                'name' => $name, 'slug' => $slug, 'description' => $description, 'motif' => $motif,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // [category, name, tagline, description, price (PHP), stock, featured]
        $products = [
            ['masks-accessories', 'Crimson Domino Mask', 'Lightweight mask with a glossy red finish.', 'A sculpted domino mask in glossy crimson resin with a soft-touch inner lining and an adjustable silk tie. Light enough to forget you are wearing it, bold enough that nobody else will.', 899, 24, 1],
            ['masks-accessories', 'Midnight Leather Gloves', 'Supple black gloves with touchscreen fingertips.', 'Lambskin-feel gloves lined with brushed knit, with conductive fingertips so you can work your phone without breaking stride. Red stitching runs along the cuff.', 1290, 15, 1],
            ['masks-accessories', 'Calling Card Pin Set', 'Three enamel pins: top hat, star and red card.', 'A trio of hard-enamel pins with black nickel plating and double clutch backs. Pin them to a lapel, a bag strap or a jacket that needs more attitude.', 450, 40, 0],
            ['masks-accessories', 'Silver Fox Mask', 'Half-face fox mask with brushed metal accents.', 'A half-face fox mask with brushed silver accents and a padded nose bridge. Ideal for costume parties, cosplay and dramatic entrances.', 1150, 4, 0],
            ['masks-accessories', 'Red Silk Neck Scarf', 'Pure red, impossible to ignore.', 'A lightweight silk-blend scarf in signal red, finished with hand-rolled edges. Wear it at the neck, on a bag, or as a statement flag.', 690, 30, 0],
            ['outerwear', 'Phantom Tailcoat', 'Long black coat with a sharp red lining.', 'A floor-sweeping tailcoat in matte black twill with a high collar and a vivid red satin lining that flashes with every turn. Two inner pockets for whatever you are carrying out.', 4990, 8, 1],
            ['outerwear', 'Rooftop Bomber Jacket', 'Water-resistant bomber with a star patch.', 'A water-resistant nylon bomber with ribbed cuffs, a quilted lining and a red star patch on the sleeve. Built for windy rooftops and late trains.', 3290, 12, 1],
            ['outerwear', 'Street Shadow Hoodie', 'Heavyweight hoodie, oversized fit.', 'A 400gsm brushed-fleece hoodie with an oversized fit, double-layer hood and a tonal halftone print across the back.', 1890, 20, 0],
            ['outerwear', 'Scarlet Trench', 'Belted trench coat in deep scarlet.', 'A double-breasted trench in deep scarlet gabardine with storm flaps and a self-tie belt. Wear it closed for mystery or open for maximum drama.', 5490, 3, 0],
            ['gadgets', 'Night Ops Phone', 'Matte black smartphone with red accents.', 'A 6.5-inch matte black phone with a red side key, a night camera mode and a two-day battery. Shipped unlocked with a tempered glass protector.', 12990, 6, 1],
            ['gadgets', 'Lookout Mini Binoculars', 'Pocket 8x21 binoculars.', 'Compact 8x21 binoculars with multi-coated lenses and a rubber-armoured body that folds down to pocket size. Great for concerts, birding and keeping watch.', 1590, 18, 0],
            ['gadgets', 'Infiltrator Wireless Earbuds', 'Noise-cancelling earbuds, 30h case.', 'Active noise-cancelling earbuds with a transparency mode for staying aware, touch controls and a charging case good for 30 hours.', 2490, 25, 1],
            ['gadgets', 'Grappling Cable Organizer', 'Magnetic cable clips, set of 6.', 'Six silicone cable clips with magnetic backs to keep chargers and earphone cables in place on desks and nightstands.', 290, 60, 0],
            ['gadgets', 'Signal Red Power Bank', '20,000 mAh fast-charging power bank.', 'A 20,000 mAh power bank with USB-C fast charging, two outputs and a red LED charge display. Enough juice for a whole night out.', 1790, 0, 0],
            ['books-journals', 'Heist Planner Journal', 'Dot-grid journal with a red elastic band.', 'A 192-page dot-grid journal with a lay-flat binding, numbered pages, an index and a red elastic closure. For plans, lists and secret schemes.', 590, 35, 1],
            ['books-journals', 'The Art of the Gentleman Thief', 'A novel of masks, mansions and misdirection.', 'A stylish mystery novel following a charming thief across a city of masked balls, rival crews and one impossible vault.', 750, 14, 0],
            ['books-journals', 'Tarot of Shadows Deck', 'Illustrated 78-card deck with guidebook.', 'A 78-card deck in stark black, red and white artwork, with a pocket guidebook explaining each card for beginners.', 990, 9, 0],
            ['books-journals', 'Lock & Key Puzzle Book', '100 logic puzzles that escalate.', 'One hundred logic puzzles, cipher challenges and maze riddles that get steadily harder. Answers sealed at the back.', 520, 2, 0],
            ['coffee-snacks', 'Midnight Roast Coffee Beans', 'Dark roast, 500g whole beans.', 'A dark roast blend of highland arabica with notes of cocoa and toasted nuts. 500g of whole beans, roasted in small batches.', 680, 50, 1],
            ['coffee-snacks', 'Hideout House Blend', 'Medium roast, smooth and chocolatey.', 'Our everyday medium roast: smooth, chocolatey and forgiving in any brewer. 250g ground for drip or French press.', 420, 45, 0],
            ['coffee-snacks', 'Spicy Curry Crackers', 'Crunchy rice crackers with curry spice.', 'Crispy rice crackers dusted with a warming curry spice blend. Dangerously snackable during late-night planning sessions.', 180, 80, 0],
            ['coffee-snacks', 'Siphon Brewer Kit', 'Glass siphon coffee maker with burner.', 'A glass vacuum siphon brewer with stand, alcohol burner and cloth filters, for coffee that is equal parts science and theatre.', 2890, 5, 0],
            ['coffee-snacks', 'Velvet Cocoa Tin', 'Rich drinking chocolate, 300g.', 'Dark drinking chocolate with a hint of sea salt in a reusable red tin. Whisk with hot milk for a velvety cup.', 350, 1, 0],
        ];

        $productRows = [];
        foreach ($products as $i => [$cat, $name, $tagline, $description, $price, $stock, $featured]) {
            $created = date('Y-m-d H:i:s', strtotime('-' . (40 - $i) . ' days'));
            $id = $this->db->table('products')->insert([
                'category_id' => $categoryIds[$cat],
                'name' => $name,
                'slug' => str_slug($name),
                'tagline' => $tagline,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'is_featured' => $featured,
                'created_at' => $created,
                'updated_at' => $created,
            ]);
            $productRows[] = ['id' => $id, 'name' => $name, 'price' => (float) $price];
        }

        // Users
        $users = [
            ['Admin Crew', 'admin@midnight.test', 'phantom-admin', 'admin', 'Mastermind'],
            ['Ana Villanueva', 'ana@midnight.test', 'shopper123', 'customer', 'Vixen'],
            ['Ryan Cruz', 'ryan@midnight.test', 'shopper123', 'customer', 'Ace'],
            ['Mika Santos', 'mika@midnight.test', 'shopper123', 'customer', null],
            ['Leo Reyes', 'leo@midnight.test', 'shopper123', 'customer', 'Raven'],
            ['Jules Navarro', 'jules@midnight.test', 'shopper123', 'customer', null],
        ];
        $customerIds = [];
        foreach ($users as $i => [$name, $email, $password, $role, $codename]) {
            $created = date('Y-m-d H:i:s', strtotime('-' . (30 - $i * 4) . ' days'));
            $id = $this->db->table('users')->insert([
                'name' => $name, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role, 'codename' => $codename, 'created_at' => $created, 'updated_at' => $created,
            ]);
            if ($role === 'customer') {
                $customerIds[] = $id;
            }
        }

        // Orders spread across the last 21 days (historic: stock is not decremented).
        $statuses = ['delivered', 'delivered', 'delivered', 'shipped', 'shipped', 'processing', 'pending', 'cancelled'];
        $cities = ['Angeles City', 'Quezon City', 'Makati', 'Cebu City', 'Davao City', 'San Fernando'];
        for ($n = 0; $n < 26; $n++) {
            $daysAgo = mt_rand(0, 20);
            $created = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -" . mt_rand(0, 600) . ' minutes'));
            $userId = $customerIds[array_rand($customerIds)];
            $status = $daysAgo <= 1 ? 'pending' : $statuses[array_rand($statuses)];

            $lines = [];
            $picked = (array) array_rand($productRows, mt_rand(1, 3));
            $subtotal = 0.0;
            foreach ($picked as $index) {
                $qty = mt_rand(1, 3);
                $line = $productRows[$index]['price'] * $qty;
                $subtotal += $line;
                $lines[] = [$productRows[$index], $qty, $line];
            }
            $shipping = $subtotal >= 3000 ? 0 : 150;

            $orderId = $this->db->table('orders')->insert([
                'user_id' => $userId,
                'reference' => 'MM-' . date('ymd', strtotime($created)) . '-' . strtoupper(substr(md5((string) $n), 0, 5)),
                'status' => $status,
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping,
                'total' => $subtotal + $shipping,
                'display_currency' => 'PHP',
                'display_rate' => 1,
                'payment_method' => ['cod', 'gcash', 'card'][mt_rand(0, 2)],
                'shipping_name' => 'Demo Customer ' . ($n + 1),
                'shipping_phone' => '0917' . mt_rand(1000000, 9999999),
                'shipping_address' => mt_rand(10, 999) . ' Rizal Street',
                'shipping_city' => $cities[array_rand($cities)],
                'notes' => null,
                'created_at' => $created,
                'updated_at' => $created,
            ]);
            foreach ($lines as [$product, $qty, $line]) {
                $this->db->table('order_items')->insert([
                    'order_id' => $orderId, 'product_id' => $product['id'], 'product_name' => $product['name'],
                    'unit_price' => $product['price'], 'quantity' => $qty, 'line_total' => $line,
                    'created_at' => $created, 'updated_at' => $created,
                ]);
            }
        }

        // Reviews
        $comments = [
            [5, 'Exactly as pictured. The red is unreal under city lights.'],
            [4, 'Great quality, shipping took a couple of days longer than expected.'],
            [5, 'My new favourite. Already ordered another as a gift.'],
            [3, 'Nice, but runs a little small. Size up.'],
            [5, 'Stylish and practical — rare combination.'],
        ];
        foreach (array_slice($productRows, 0, 12) as $i => $product) {
            foreach (array_slice($customerIds, 0, 1 + $i % 3) as $j => $userId) {
                [$rating, $comment] = $comments[($i + $j) % count($comments)];
                $created = date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 15) . ' days'));
                $this->db->table('reviews')->insert([
                    'product_id' => $product['id'], 'user_id' => $userId, 'rating' => $rating, 'comment' => $comment,
                    'created_at' => $created, 'updated_at' => $created,
                ]);
            }
        }
    }
}
