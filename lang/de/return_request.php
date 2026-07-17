<?php

return [
    'slug' => 'rucksendungen-und-reklamationen',
    'page_title' => 'Rücksendungen und Reklamationen',
    'eyebrow' => 'Kundenformular',
    'heading' => 'Formular für Rücksendungen und Reklamationen',
    'subheading' => 'Senden Sie uns die Bestelldaten und die Artikel, die Sie zurücksenden oder reklamieren möchten.',
    'sent_status' => 'Vielen Dank. Ihre Anfrage wurde erfolgreich gesendet.',
    'captcha_failed' => 'Die Sicherheitsprüfung ist fehlgeschlagen. Bitte versuchen Sie es erneut.',
    'validation' => [
        'required' => 'Das Feld :attribute ist erforderlich.',
        'email' => 'Das Feld :attribute muss eine gültige E-Mail-Adresse enthalten.',
        'min_string' => 'Das Feld :attribute muss mindestens :min Zeichen enthalten.',
        'max_string' => 'Das Feld :attribute darf höchstens :max Zeichen enthalten.',
        'security_check' => 'Sicherheitsprüfung',
        'inline' => [
            'email_required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email_invalid' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'order_number_required' => 'Bitte geben Sie die Bestellnummer ein.',
            'return_items_required' => 'Bitte geben Sie die Artikel für die Rücksendung ein.',
            'return_items_min' => 'Die Artikelangabe muss mindestens 2 Zeichen enthalten.',
        ],
    ],
    'form' => [
        'email' => 'E-Mail-Adresse',
        'order_number' => 'Bestellnummer',
        'return_items' => 'Artikel für die Rücksendung',
        'return_items_placeholder' => 'Zum Beispiel Artikelname, SKU, Größe oder Menge',
        'note' => 'Anmerkung',
        'note_placeholder' => 'Weitere Informationen zur Rücksendung oder Reklamation',
        'submit' => 'Anfrage senden',
    ],
    'mail' => [
        'subject' => '[Rücksendung/Reklamation] Bestellung :order',
        'subject_fallback' => '[Rücksendung/Reklamation] Neue Anfrage',
        'email' => 'E-Mail-Adresse',
        'order_number' => 'Bestellnummer',
        'return_items' => 'Artikel für die Rücksendung',
        'note' => 'Anmerkung',
    ],
    'help' => [
        'title' => 'Welche Angaben sind erforderlich?',
        'body' => 'Führen Sie die Artikel auf, die Sie zurücksenden oder reklamieren möchten. Die Anmerkung kann leer bleiben.',
    ],
];
