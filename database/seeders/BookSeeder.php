<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Seed sekitar 30 buku contoh dari 6 kategori.
     */
    public function run(): void
    {
        $categoryIds = Category::query()->pluck('id', 'name');

        $books = [
            ['Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 'A1', 'Fiksi'],
            ['Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, 'A1', 'Fiksi'],
            ['Ronggeng Dukuh Paruk', 'Ahmad Tohari', 'Gramedia', 1982, 'A2', 'Fiksi'],
            ['Perahu Kertas', 'Dee Lestari', 'Bentang Pustaka', 2009, 'A2', 'Fiksi'],
            ['Cantik Itu Luka', 'Eka Kurniawan', 'Gramedia', 2002, 'A3', 'Fiksi'],
            ['Negeri 5 Menara', 'Ahmad Fuadi', 'Gramedia', 2009, 'A3', 'Fiksi'],
            ['Pulang', 'Leila S. Chudori', 'Kepustakaan Populer Gramedia', 2012, 'A4', 'Fiksi'],
            ['Hujan', 'Tere Liye', 'Gramedia', 2016, 'A4', 'Fiksi'],
            ['Filosofi Teras', 'Henry Manampiring', 'Kompas', 2018, 'B1', 'Nonfiksi'],
            ['Sapiens: Sejarah Singkat Umat Manusia', 'Yuval Noah Harari', 'Kepustakaan Populer Gramedia', 2017, 'B1', 'Nonfiksi'],
            ['Homo Deus', 'Yuval Noah Harari', 'Kepustakaan Populer Gramedia', 2018, 'B2', 'Nonfiksi'],
            ['Atomic Habits', 'James Clear', 'Gramedia', 2019, 'B2', 'Nonfiksi'],
            ['Berpikir Cepat dan Lambat', 'Daniel Kahneman', 'Kepustakaan Populer Gramedia', 2016, 'B3', 'Nonfiksi'],
            ['The 7 Habits of Highly Effective People', 'Stephen R. Covey', 'Binarupa Aksara', 1997, 'B3', 'Nonfiksi'],
            ['Sejarah Indonesia Modern', 'M. C. Ricklefs', 'Serambi', 2008, 'C1', 'Sejarah'],
            ['Nusantara: Sejarah Indonesia', 'Bernard H. M. Vlekke', 'Kepustakaan Populer Gramedia', 2008, 'C1', 'Sejarah'],
            ['Api Sejarah', 'Ahmad Mansur Suryanegara', 'Salamadani', 2009, 'C2', 'Sejarah'],
            ['Sejarah Peradaban Islam', 'Ahmad Syalabi', 'Pustaka Al Husna', 2003, 'C2', 'Sejarah'],
            ['Banjarmasin: Sejarah Kota', 'Tim Penulis Daerah', 'Pemerintah Kota Banjarmasin', 2015, 'C3', 'Sejarah'],
            ['Riyadhus Shalihin', 'Imam An-Nawawi', 'Al-Haramain', 2006, 'D1', 'Agama'],
            ['Fiqih Sunnah', 'Sayyid Sabiq', 'Al-Ma\'arif', 1990, 'D1', 'Agama'],
            ['Tafsir Al-Mishbah', 'M. Quraish Shihab', 'Lentera Hati', 2000, 'D2', 'Agama'],
            ['Tasawuf Modern', 'Hamka', 'Republika', 2016, 'D2', 'Agama'],
            ['Petualangan Si Kancil', 'Tim Penulis Anak', 'Erlangga for Kids', 2010, 'E1', 'Anak-Anak'],
            ['Cerita Rakyat Nusantara', 'Tim Penulis Anak', 'Gramedia', 2014, 'E1', 'Anak-Anak'],
            ['Si Juki: Nggak Ada yang Peduli', 'Faza Ibnu Ubaidillah', 'Elex Media Komputindo', 2015, 'E2', 'Anak-Anak'],
            ['Ensiklopedia Anak: Tata Surya', 'Tim Penulis Anak', 'Tiga Serangkai', 2018, 'E2', 'Anak-Anak'],
            ['Algoritma dan Pemrograman', 'Rinaldi Munir', 'Informatika', 2016, 'F1', 'Sains & Teknologi'],
            ['Kecerdasan Buatan Dasar', 'Suyanto', 'Informatika', 2018, 'F1', 'Sains & Teknologi'],
            ['Fisika Dasar untuk Sains dan Teknik', 'Mikrajuddin Abdullah', 'ITB', 2017, 'F2', 'Sains & Teknologi'],
            ['Bioteknologi Molekuler', 'Fatchiyah', 'Erlangga', 2014, 'F2', 'Sains & Teknologi'],
        ];

        foreach ($books as [$title, $author, $publisher, $year, $shelf, $category]) {
            $total = random_int(1, 8);

            Book::query()->create([
                'category_id' => $categoryIds[$category],
                'title' => $title,
                'author' => $author,
                'publisher' => $publisher,
                'publication_year' => $year,
                'shelf_location' => $shelf,
                'total_copies' => $total,
                'available_copies' => $total,
            ]);
        }
    }
}
