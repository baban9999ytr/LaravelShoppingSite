<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Feature;
use App\Models\FeatureValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GeneralSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create or retrieve the User
        $user = User::firstOrCreate(
            ['email' => 'gogsalmustafa19@gmail.com'],
            [
                'name' => 'Mustafa Göksal',
                'password' => Hash::make('qwe123'),
                'email_verified_at' => now(),
                'role' => 'admin',
            ]
        );

        // 2. Safely Create and attach a Team if the user doesn't have one
        if (class_exists(Team::class) && ! $user->current_team_id) {
            $teamData = [
                'name' => "Mustafa's Team",
            ];

            // Dynamically assign owner column depending on your schema
            if (Schema::hasColumn('teams', 'user_id')) {
                $teamData['user_id'] = $user->id;
            } elseif (Schema::hasColumn('teams', 'owner_id')) {
                $teamData['owner_id'] = $user->id;
            }

            if (Schema::hasColumn('teams', 'personal_team')) {
                $teamData['personal_team'] = true;
            }

            if (Schema::hasColumn('teams', 'slug')) {
                $teamData['slug'] = Str::slug("Mustafa's Team");
            }

            $team = Team::forceCreate($teamData);

            $user->forceFill([
                'current_team_id' => $team->id,
            ])->save();
        }

        // --- REST OF YOUR SEEDER CONTENT ---

        $urunler = Category::create([
            'name' => 'Ürünler',
            'slug' => 'urunler',
        ]);

        $saticilar = Category::create([
            'name' => 'Satıcılar / Markalar',
            'slug' => 'saticilar-markalar',
            'parent_id' => $urunler->id,
        ]);

        $brandNames = ['Tudors', 'Altınyıldız', 'Mavi'];
        $brandCategories = [];
        foreach ($brandNames as $brand) {
            $brandCategories[$brand] = Category::create([
                'name' => $brand,
                'slug' => Str::slug($brand),
                'parent_id' => $saticilar->id,
            ]);
        }

        $sezonlar = Category::create([
            'name' => 'Sezonlar',
            'slug' => 'sezonlar',
            'parent_id' => $urunler->id,
        ]);

        $seasonNames = ['Kışlık', 'Yazlık', 'Sonbaharlık', 'İlkbaharlık'];
        $seasonCategories = [];
        foreach ($seasonNames as $season) {
            $seasonCategories[$season] = Category::create([
                'name' => $season,
                'slug' => Str::slug($season),
                'parent_id' => $sezonlar->id,
            ]);
        }

        $giyim = Category::create([
            'name' => 'Giyim',
            'slug' => 'giyim',
            'parent_id' => $urunler->id,
        ]);

        $kadinGiyim = Category::create([
            'name' => 'Kadın',
            'slug' => 'kadin',
            'parent_id' => $giyim->id,
        ]);

        $erkekGiyim = Category::create([
            'name' => 'Erkek',
            'slug' => 'erkek',
            'parent_id' => $giyim->id,
        ]);

        $unisexGiyim = Category::create([
            'name' => 'Unisex',
            'slug' => 'unisex',
            'parent_id' => $giyim->id,
        ]);

        $kadinUst = Category::create([
            'name' => 'Üst Giyim',
            'slug' => 'kadin-ust-giyim',
            'parent_id' => $kadinGiyim->id,
        ]);

        $kadinMont = Category::create([
            'name' => 'Mont',
            'slug' => 'kadin-mont',
            'parent_id' => $kadinUst->id,
        ]);

        $erkekUst = Category::create([
            'name' => 'Üst Giyim',
            'slug' => 'erkek-ust-giyim',
            'parent_id' => $erkekGiyim->id,
        ]);

        $erkekGömlek = Category::create([
            'name' => 'Gömlek',
            'slug' => 'erkek-gomlek',
            'parent_id' => $erkekUst->id,
        ]);

        $erkekAlt = Category::create([
            'name' => 'Alt Giyim',
            'slug' => 'erkek-alt-giyim',
            'parent_id' => $erkekGiyim->id,
        ]);

        $erkekPantolon = Category::create([
            'name' => 'Kot Pantolon',
            'slug' => 'erkek-kot-pantolon',
            'parent_id' => $erkekAlt->id,
        ]);

        $renkFeature = Feature::create([
            'name' => 'Renk',
            'slug' => 'renk',
        ]);

        $colors = [
            'Siyah', 'Beyaz', 'Kırmızı', 'Mavi', 'Yeşil',
            'Sarı', 'Lacivert', 'Gri', 'Kahverengi', 'Bej',
            'Bordo', 'Pembe', 'Mor', 'Turuncu', 'Haki',
            'Antrasit', 'Turkuaz', 'Krem', 'Taba', 'Zümrüt Yeşili',
        ];

        $colorValues = [];
        foreach ($colors as $color) {
            $colorValues[$color] = FeatureValue::create([
                'feature_id' => $renkFeature->id,
                'value' => $color,
            ]);
        }

        $bedenFeature = Feature::create([
            'name' => 'Beden',
            'slug' => 'beden',
        ]);

        $sizes = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $sizeValues = [];
        foreach ($sizes as $size) {
            $sizeValues[$size] = FeatureValue::create([
                'feature_id' => $bedenFeature->id,
                'value' => $size,
            ]);
        }

        $kumasFeature = Feature::create([
            'name' => 'Kumaş Tipi',
            'slug' => 'kumas-tipi',
        ]);

        $fabrics = ['%100 Pamuk', 'Keten', 'Kot / Denim', 'Polyester', 'Yünlü'];
        $fabricValues = [];
        foreach ($fabrics as $fabric) {
            $fabricValues[$fabric] = FeatureValue::create([
                'feature_id' => $kumasFeature->id,
                'value' => $fabric,
            ]);
        }

        $p1 = Product::create([
            'name' => 'Klasik Slim-Fit Oxford Gömlek',
            'slug' => 'klasik-slim-fit-oxford-gomlek',
            'meta_title' => 'Klasik Slim-Fit Oxford Gömlek | Tudors',
            'meta_description' => '%100 Pamuk kumaştan üretilmiş, şık ve konforlu erkek gömlek.',
            'price' => 749.90,
            'description' => 'Günlük ve iş hayatınızda rahatlıkla tercih edebileceğiniz şık tasarım.',
            'image_url' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600&q=80',
        ]);

        $p1->categories()->sync([
            $erkekGömlek->id,
            $brandCategories['Tudors']->id,
            $seasonCategories['Sonbaharlık']->id,
        ]);

        $p1->featureValues()->sync([
            $colorValues['Beyaz']->id,
            $colorValues['Lacivert']->id,
            $fabricValues['%100 Pamuk']->id,
        ]);

        ProductVariant::create([
            'product_id' => $p1->id,
            'sku' => 'TUD-GMLK-BYZ-M',
            'price' => 749.90,
            'stock' => 25,
            'color' => 'Beyaz',
            'size' => 'M',
            'image_url' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600&q=80',
        ]);

        ProductVariant::create([
            'product_id' => $p1->id,
            'sku' => 'TUD-GMLK-LAC-L',
            'price' => 749.90,
            'stock' => 18,
            'color' => 'Lacivert',
            'size' => 'L',
            'image_url' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=600&q=80',
        ]);

        $p2 = Product::create([
            'name' => 'Kapüşonlu Şişme Kadın Mont',
            'slug' => 'kapusonlu-sisme-kadin-mont',
            'meta_title' => 'Kapüşonlu Şişme Kadın Mont | Kışlık Koleksiyon',
            'meta_description' => 'Soğuk kış günleri için tasarlanmış su geçirmez kadın şişme mont.',
            'price' => 1499.00,
            'description' => 'Rüzgara dayanıklı, içi elyaf dolgulu sıcak tutan mont.',
            'image_url' => 'https://images.unsplash.com/photo-1544441893-675973e31985?w=600&q=80',
        ]);

        $p2->categories()->sync([
            $kadinMont->id,
            $brandCategories['Mavi']->id,
            $seasonCategories['Kışlık']->id,
        ]);

        $p2->featureValues()->sync([
            $colorValues['Siyah']->id,
            $colorValues['Haki']->id,
            $fabricValues['Polyester']->id,
        ]);

        ProductVariant::create([
            'product_id' => $p2->id,
            'sku' => 'MAV-KDN-MNT-SYH-S',
            'price' => 1499.00,
            'stock' => 10,
            'color' => 'Siyah',
            'size' => 'S',
            'image_url' => 'https://images.unsplash.com/photo-1544441893-675973e31985?w=600&q=80',
        ]);

        $p3 = Product::create([
            'name' => 'Slim Straight Kot Pantolon',
            'slug' => 'slim-straight-kot-pantolon',
            'meta_title' => 'Erkek Slim Straight Kot Pantolon | Altınyıldız',
            'meta_description' => 'Kaliteli denim kumaştan imal edilmiş uzun ömürlü erkek kot pantolon.',
            'price' => 899.90,
            'description' => 'Esnek yapısıyla gün boyu hareket özgürlüğü sunar.',
            'image_url' => 'https://images.unsplash.com/photo-1542272604-780c96856592?w=600&q=80',
        ]);

        $p3->categories()->sync([
            $erkekPantolon->id,
            $brandCategories['Altınyıldız']->id,
            $seasonCategories['Yazlık']->id,
            $seasonCategories['Sonbaharlık']->id,
        ]);

        $p3->featureValues()->sync([
            $colorValues['Mavi']->id,
            $fabricValues['Kot / Denim']->id,
        ]);

        Favorite::updateOrCreate([
            'user_id' => $user->id,
            'product_id' => $p1->id,
        ], ['value' => 'Favori Ürün 1']);

        Favorite::updateOrCreate([
            'user_id' => $user->id,
            'product_id' => $p2->id,
        ], ['value' => 'Favori Ürün 2']);
    }
}
