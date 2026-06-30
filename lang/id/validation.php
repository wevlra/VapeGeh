<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':Attribute harus diterima.',
    'accepted_if' => ':Attribute harus diterima ketika :other adalah :value.',
    'active_url' => ':Attribute bukan URL yang valid.',
    'after' => ':Attribute harus berisi tanggal setelah :date.',
    'after_or_equal' => ':Attribute harus berisi tanggal setelah atau sama dengan :date.',
    'alpha' => ':Attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':Attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
    'alpha_num' => ':Attribute hanya boleh berisi huruf dan angka.',
    'array' => ':Attribute harus berupa sebuah array.',
    'ascii' => ':Attribute hanya boleh berisi karakter alfanumerik dan simbol single-byte.',
    'before' => ':Attribute harus berisi tanggal sebelum :date.',
    'before_or_equal' => ':Attribute harus berisi tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':Attribute harus memiliki anggota antara :min dan :max.',
        'file' => ':Attribute harus berukuran antara :min dan :max kilobita.',
        'numeric' => ':Attribute harus bernilai antara :min dan :max.',
        'string' => ':Attribute harus berisi antara :min dan :max karakter.',
    ],
    'boolean' => ':Attribute harus bernilai true atau false.',
    'can' => ':Attribute mengandung nilai yang tidak sah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date' => ':Attribute bukan tanggal yang valid.',
    'date_equals' => ':Attribute harus berisi tanggal yang sama dengan :date.',
    'date_format' => ':Attribute tidak cocok dengan format :format.',
    'decimal' => ':Attribute harus memiliki :decimal tempat desimal.',
    'declined' => ':Attribute harus ditolak.',
    'declined_if' => ':Attribute harus ditolak ketika :other adalah :value.',
    'different' => ':Attribute dan :other harus berbeda.',
    'digits' => ':Attribute harus terdiri dari :digits angka.',
    'digits_between' => ':Attribute harus terdiri dari antara :min dan :max angka.',
    'dimensions' => ':Attribute tidak memiliki dimensi gambar yang valid.',
    'distinct' => ':Attribute memiliki nilai yang duplikat.',
    'doesnt_end_with' => ':Attribute tidak boleh diakhiri dengan salah satu dari berikut: :values.',
    'doesnt_start_with' => ':Attribute tidak boleh diawali dengan salah satu dari berikut: :values.',
    'email' => ':Attribute harus berupa alamat surel yang valid.',
    'ends_with' => ':Attribute harus diakhiri dengan salah satu dari berikut: :values.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'extensions' => ':Attribute harus memiliki ekstensi: :values.',
    'file' => ':Attribute harus berupa sebuah berkas.',
    'filled' => ':Attribute harus memiliki nilai.',
    'gt' => [
        'array' => ':Attribute harus memiliki lebih dari :max anggota.',
        'file' => ':Attribute harus berukuran lebih dari :max kilobita.',
        'numeric' => ':Attribute harus bernilai lebih besar dari :value.',
        'string' => ':Attribute harus berisi lebih dari :max karakter.',
    ],
    'gte' => [
        'array' => ':Attribute harus memiliki :max anggota atau lebih.',
        'file' => ':Attribute harus berukuran lebih dari atau sama dengan :max kilobita.',
        'numeric' => ':Attribute harus bernilai lebih dari atau sama dengan :value.',
        'string' => ':Attribute harus berisi lebih dari atau sama dengan :max karakter.',
    ],
    'hex_color' => ':Attribute harus berupa warna heksadesimal yang valid.',
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'in_array' => ':Attribute tidak ada di dalam :other.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'ip' => ':Attribute harus berupa alamat IP yang valid.',
    'ipv4' => ':Attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => ':Attribute harus berupa alamat IPv6 yang valid.',
    'json' => ':Attribute harus berupa JSON yang valid.',
    'list' => ':Attribute harus berupa daftar (list).',
    'lowercase' => ':Attribute harus berupa huruf kecil semua.',
    'lt' => [
        'array' => ':Attribute harus memiliki kurang dari :min anggota.',
        'file' => ':Attribute harus berukuran kurang dari :min kilobita.',
        'numeric' => ':Attribute harus bernilai kurang dari :value.',
        'string' => ':Attribute harus berisi kurang dari :min karakter.',
    ],
    'lte' => [
        'array' => ':Attribute harus memiliki tidak lebih dari :min anggota.',
        'file' => ':Attribute harus berukuran kurang dari atau sama dengan :min kilobita.',
        'numeric' => ':Attribute harus bernilai kurang dari atau sama dengan :value.',
        'string' => ':Attribute harus berisi kurang dari atau sama dengan :min karakter.',
    ],
    'mac_address' => ':Attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => ':Attribute maksimal memiliki :max anggota.',
        'file' => ':Attribute maksimal berukuran :max kilobita.',
        'numeric' => ':Attribute maksimal bernilai :max.',
        'string' => ':Attribute maksimal berisi :max karakter.',
    ],
    'max_digits' => ':Attribute maksimal memiliki :max digit.',
    'mimes' => ':Attribute harus berupa berkas dengan tipe: :values.',
    'mimetypes' => ':Attribute harus berupa berkas dengan tipe: :values.',
    'min' => [
        'array' => ':Attribute minimal memiliki :min anggota.',
        'file' => ':Attribute minimal berukuran :min kilobita.',
        'numeric' => ':Attribute minimal bernilai :min.',
        'string' => ':Attribute minimal berisi :min karakter.',
    ],
    'min_digits' => ':Attribute minimal memiliki :min digit.',
    'missing' => ':Attribute harus tidak ada.',
    'missing_if' => ':Attribute harus tidak ada ketika :other adalah :value.',
    'missing_unless' => ':Attribute harus tidak ada kecuali :other adalah :value.',
    'missing_with' => ':Attribute harus tidak ada ketika :values tersedia.',
    'missing_with_all' => ':Attribute harus tidak ada ketika :values tersedia.',
    'multiple_of' => ':Attribute harus merupakan kelipatan dari :value.',
    'not_in' => ':Attribute yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => ':Attribute harus berupa angka.',
    'password' => [
        'letters' => ':Attribute harus mengandung setidaknya satu huruf.',
        'mixed' => ':Attribute harus mengandung setidaknya satu huruf besar dan satu huruf kecil.',
        'numbers' => ':Attribute harus mengandung setidaknya satu angka.',
        'symbols' => ':Attribute harus mengandung setidaknya satu simbol.',
        'uncompromised' => ':Attribute yang diberikan telah muncul di kebocoran data. Silakan pilih :attribute yang berbeda.',
    ],
    'present' => ':Attribute harus ada.',
    'present_if' => ':Attribute harus ada ketika :other adalah :value.',
    'present_unless' => ':Attribute harus ada kecuali :other adalah :value.',
    'present_with' => ':Attribute harus ada ketika :values tersedia.',
    'present_with_all' => ':Attribute harus ada ketika :values ada.',
    'prohibited' => ':Attribute tidak boleh ada.',
    'prohibited_if' => ':Attribute tidak boleh ada ketika :other adalah :value.',
    'prohibited_unless' => ':Attribute tidak boleh ada kecuali :other ada di dalam :values.',
    'prohibits' => ':Attribute melarang :other untuk ada.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'required_array_keys' => ':Attribute harus berisi entri untuk: :values.',
    'required_if' => ':Attribute wajib diisi ketika :other adalah :value.',
    'required_if_accepted' => ':Attribute wajib diisi ketika :other diterima.',
    'required_unless' => ':Attribute wajib diisi kecuali :other ada di dalam :values.',
    'required_with' => ':Attribute wajib diisi ketika :values tersedia.',
    'required_with_all' => ':Attribute wajib diisi ketika :values tersedia.',
    'required_without' => ':Attribute wajib diisi ketika :values tidak tersedia.',
    'required_without_all' => ':Attribute wajib diisi ketika tidak satupun :values tersedia.',
    'same' => ':Attribute dan :other harus sama.',
    'size' => [
        'array' => ':Attribute harus memuat :size anggota.',
        'file' => ':Attribute harus berukuran :size kilobita.',
        'numeric' => ':Attribute harus bernilai :size.',
        'string' => ':Attribute harus berisi :size karakter.',
    ],
    'starts_with' => ':Attribute harus diawali dengan salah satu dari berikut: :values.',
    'string' => ':Attribute harus berupa string.',
    'timezone' => ':Attribute harus berupa zona waktu yang valid.',
    'unique' => ':Attribute sudah digunakan.',
    'uploaded' => ':Attribute gagal diunggah.',
    'uppercase' => ':Attribute harus berupa huruf kapital semua.',
    'url' => 'Format :attribute tidak valid.',
    'ulid' => ':Attribute harus berupa ULID yang valid.',
    'uuid' => ':Attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'location_id' => 'Lokasi',
        'buyer_id' => 'Pembeli',
        'payment_method' => 'Metode Pembayaran',
        'product_id' => 'Produk',
        'price_id' => 'Harga Jual',
        'manual_price' => 'Harga Manual',
        'paid_amount' => 'Jumlah Dibayar',
        'notes' => 'Catatan',
        'qty' => 'Jumlah',
        'price' => 'Harga',
        'amount' => 'Jumlah',
        'purchase_price' => 'Harga Beli',
        'selling_price' => 'Harga Jual',
        'name' => 'Nama',
        'description' => 'Deskripsi',
        'category' => 'Kategori',
        'date' => 'Tanggal',
        'label' => 'Label',
        'sku' => 'SKU',
        'email' => 'Email',
        'phone' => 'Telepon',
        'address' => 'Alamat',
        'stock_qty' => 'Stok',
    ],

];
