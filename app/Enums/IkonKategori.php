<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Ikon yang boleh dipilih untuk sebuah kategori.
 *
 * Daftar tertutup, sengaja — dan nilainya adalah nama ikon Material yang sudah
 * dikenali aplikasi Flutter, jadi tidak ada pemetaan tambahan di sisi mana pun.
 * Membuka seluruh pustaka Material berarti memberi pemilik toko dua ribu
 * pilihan untuk satu keputusan yang tidak penting, dan chip kasir yang isinya
 * ikon "cloud" berhenti terbaca sebagai kategori makanan.
 *
 * Urutannya mengikuti apa yang paling sering dijual warung: minuman dulu, lalu
 * makanan, lalu sisanya.
 */
enum IkonKategori: string
{
    case Coffee = 'coffee_outlined';
    case LocalDrink = 'local_drink_outlined';
    case LocalCafe = 'local_cafe_outlined';
    case EmojiFoodBeverage = 'emoji_food_beverage_outlined';
    case WineBar = 'wine_bar_outlined';
    case RamenDining = 'ramen_dining_outlined';
    case RiceBowl = 'rice_bowl_outlined';
    case LunchDining = 'lunch_dining_outlined';
    case LocalPizza = 'local_pizza_outlined';
    case SetMeal = 'set_meal_outlined';
    case BakeryDining = 'bakery_dining_outlined';
    case Cake = 'cake_outlined';
    case Icecream = 'icecream_outlined';
    case Cookie = 'cookie_outlined';
    case EggAlt = 'egg_alt_outlined';
    case LocalGroceryStore = 'local_grocery_store_outlined';
    case Soap = 'soap_outlined';
    case Category = 'category_outlined';

    /** @return list<string> */
    public static function nilai(): array
    {
        return array_map(static fn (self $ikon): string => $ikon->value, self::cases());
    }
}
