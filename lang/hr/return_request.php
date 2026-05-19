<?php

return [
    'page_title' => 'Povrat i reklamacije',
    'eyebrow' => 'Obrazac za kupce',
    'heading' => 'Forma za povrat i reklamacije',
    'subheading' => 'Pošaljite podatke o narudžbi i artiklima koje želite vratiti ili reklamirati.',
    'sent_status' => 'Hvala. Vaš zahtjev za povrat ili reklamaciju je uspješno poslan.',
    'captcha_failed' => 'Potvrda sigurnosti nije uspjela. Pokušajte ponovno.',
    'validation' => [
        'required' => 'Polje :attribute je obavezno.',
        'email' => 'Polje :attribute mora biti ispravna email adresa.',
        'min_string' => 'Polje :attribute mora imati najmanje :min znaka.',
        'max_string' => 'Polje :attribute ne smije imati više od :max znakova.',
        'security_check' => 'sigurnosna provjera',
        'inline' => [
            'email_required' => 'Unesite email adresu.',
            'email_invalid' => 'Unesite ispravnu email adresu.',
            'order_number_required' => 'Unesite broj narudžbe.',
            'return_items_required' => 'Unesite artikle za povrat.',
            'return_items_min' => 'Artikli za povrat moraju imati najmanje 2 znaka.',
        ],
    ],
    'form' => [
        'email' => 'Email korisnika',
        'order_number' => 'Broj narudžbe',
        'return_items' => 'Artikli za povrat',
        'return_items_placeholder' => 'Npr. naziv artikla, šifra, veličina ili količina',
        'note' => 'Napomena',
        'note_placeholder' => 'Dodatne informacije o povratu ili reklamaciji',
        'submit' => 'Pošalji zahtjev',
    ],
    'mail' => [
        'subject' => '[Povrat/Reklamacija] Narudžba :order',
        'subject_fallback' => '[Povrat/Reklamacija] Novi zahtjev',
        'email' => 'Email korisnika',
        'order_number' => 'Broj narudžbe',
        'return_items' => 'Artikli za povrat',
        'note' => 'Napomena',
    ],
    'help' => [
        'title' => 'Što upisati?',
        'body' => 'U polje artikala navedite proizvode koje vraćate ili reklamirate. Napomena može ostati prazna ako nemate dodatnih informacija.',
    ],
];
