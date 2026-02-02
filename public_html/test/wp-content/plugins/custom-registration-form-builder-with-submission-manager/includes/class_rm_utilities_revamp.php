<?php
class RM_Utilities_Revamp {
    public $instance;
    public $table_name_for;
    public static $script_handle;

    public function __construct() {
        $script_handle = array();
    }

    public static function localize_time($date_string, $dateformatstring = null, $advanced = false, $is_timestamp = false) {
        if ($is_timestamp) {
            $date_string = gmdate('Y-m-d H:i:s', $date_string);
        }

        if (!$dateformatstring) {
            $df = get_option('date_format', null) ?: 'd M Y';
            $tf = get_option('time_format', null) ?: 'h:ia';
            $dateformatstring = $df . ' @ ' . $tf;
        }
        //$offset = floatval(get_option('gmt_offset'));
        if ($is_timestamp) {
            $result = get_date_from_gmt($date_string, $dateformatstring);
        } else {
            //$result = date_i18n($dateformatstring, strtotime($date_string));
            $result = wp_date($dateformatstring, strtotime($date_string));
        }
        //$result = get_date_from_gmt($date_string, $dateformatstring);
        return $result;
    }

    public static function convert_to_mysql_timestamp($unix_timestamp) {
        return date("Y-m-d H:i:s", intval($unix_timestamp));
    }

    public static function format_on_time($t) {
        $ts = strtotime($t);
        if ($ts >= strtotime("today"))
            return date('g:i A', $ts) . ' today';
        else if ($ts >= strtotime("yesterday"))
            return date('g:i A', $ts) . ' yesterday';
        else {
            $on = self::convert_to_mysql_timestamp($ts);
            $on = self::localize_time($on, 'd M Y, h:i A');
            return $on;
        }
    }

    public static function get_usa_states() {
        return array(
            'AL' => __('Alabama','custom-registration-form-builder-with-submission-manager'),
            'AK' => __('Alaska','custom-registration-form-builder-with-submission-manager'),
            'AZ' => __('Arizona','custom-registration-form-builder-with-submission-manager'),
            'AR' => __('Arkansas','custom-registration-form-builder-with-submission-manager'),
            'AA' => __('Armed Forces America','custom-registration-form-builder-with-submission-manager'),
            'AE' => __('Armed Forces Europe','custom-registration-form-builder-with-submission-manager'),
            'AP' => __('Armed Forces Pacific','custom-registration-form-builder-with-submission-manager'),
            'CA' => __('California','custom-registration-form-builder-with-submission-manager'),
            'CO' => __('Colorado','custom-registration-form-builder-with-submission-manager'),
            'CT' => __('Connecticut','custom-registration-form-builder-with-submission-manager'),
            'DE' => __('Delaware','custom-registration-form-builder-with-submission-manager'),
            'DC' => __('District Of Columbia','custom-registration-form-builder-with-submission-manager'),
            'FL' => __('Florida','custom-registration-form-builder-with-submission-manager'),
            'GA' => __('Georgia','custom-registration-form-builder-with-submission-manager'),
            'HI' => __('Hawaii','custom-registration-form-builder-with-submission-manager'),
            'ID' => __('Idaho','custom-registration-form-builder-with-submission-manager'),
            'IL' => __('Illinois','custom-registration-form-builder-with-submission-manager'),
            'IN' => __('Indiana','custom-registration-form-builder-with-submission-manager'),
            'IA' => __('Iowa','custom-registration-form-builder-with-submission-manager'),
            'KS' => __('Kansas','custom-registration-form-builder-with-submission-manager'),
            'KY' => __('Kentucky','custom-registration-form-builder-with-submission-manager'),
            'LA' => __('Louisiana','custom-registration-form-builder-with-submission-manager'),
            'ME' => __('Maine','custom-registration-form-builder-with-submission-manager'),
            'MD' => __('Maryland','custom-registration-form-builder-with-submission-manager'),
            'MA' => __('Massachusetts','custom-registration-form-builder-with-submission-manager'),
            'MI' => __('Michigan','custom-registration-form-builder-with-submission-manager'),
            'MN' => __('Minnesota','custom-registration-form-builder-with-submission-manager'),
            'MS' => __('Mississippi','custom-registration-form-builder-with-submission-manager'),
            'MO' => __('Missouri','custom-registration-form-builder-with-submission-manager'),
            'MT' => __('Montana','custom-registration-form-builder-with-submission-manager'),
            'NE' => __('Nebraska','custom-registration-form-builder-with-submission-manager'),
            'NV' => __('Nevada','custom-registration-form-builder-with-submission-manager'),
            'NH' => __('New Hampshire','custom-registration-form-builder-with-submission-manager'),
            'NJ' => __('New Jersey','custom-registration-form-builder-with-submission-manager'),
            'NM' => __('New Mexico','custom-registration-form-builder-with-submission-manager'),
            'NY' => __('New York','custom-registration-form-builder-with-submission-manager'),
            'NC' => __('North Carolina','custom-registration-form-builder-with-submission-manager'),
            'ND' => __('North Dakota','custom-registration-form-builder-with-submission-manager'),
            'OH' => __('Ohio','custom-registration-form-builder-with-submission-manager'),
            'OK' => __('Oklahoma','custom-registration-form-builder-with-submission-manager'),
            'OR' => __('Oregon','custom-registration-form-builder-with-submission-manager'),
            'PA' => __('Pennsylvania','custom-registration-form-builder-with-submission-manager'),
            'RI' => __('Rhode Island','custom-registration-form-builder-with-submission-manager'),
            'SC' => __('South Carolina','custom-registration-form-builder-with-submission-manager'),
            'SD' => __('South Dakota','custom-registration-form-builder-with-submission-manager'),
            'TN' => __('Tennessee','custom-registration-form-builder-with-submission-manager'),
            'TX' => __('Texas','custom-registration-form-builder-with-submission-manager'),
            'UT' => __('Utah','custom-registration-form-builder-with-submission-manager'),
            'VT' => __('Vermont','custom-registration-form-builder-with-submission-manager'),
            'VA' => __('Virginia','custom-registration-form-builder-with-submission-manager'),
            'WA' => __('Washington','custom-registration-form-builder-with-submission-manager'),
            'WV' => __('West Virginia','custom-registration-form-builder-with-submission-manager'),
            'WI' => __('Wisconsin','custom-registration-form-builder-with-submission-manager'),
            'WY' => __('Wyoming','custom-registration-form-builder-with-submission-manager'),
        );
    }

    public static function get_canadian_provinces() {
        return array(
            'AB' => __('Alberta','custom-registration-form-builder-with-submission-manager'),
            'BC' => __('British Columbia','custom-registration-form-builder-with-submission-manager'),
            'MB' => __('Manitoba','custom-registration-form-builder-with-submission-manager'),
            'NB' => __('New Brunswick','custom-registration-form-builder-with-submission-manager'),
            'NL' => __('Newfoundland and Labrador','custom-registration-form-builder-with-submission-manager'),
            'NT' => __('Northwest Territories','custom-registration-form-builder-with-submission-manager'),
            'NS' => __('Nova Scotia','custom-registration-form-builder-with-submission-manager'),
            'NU' => __('Nunavut','custom-registration-form-builder-with-submission-manager'),
            'ON' => __('Ontario','custom-registration-form-builder-with-submission-manager'),
            'PE' => __('Prince Edward Island','custom-registration-form-builder-with-submission-manager'),
            'QC' => __('QuÃ©bec','custom-registration-form-builder-with-submission-manager'),
            'SK' => __('Saskatchewan','custom-registration-form-builder-with-submission-manager'),
            'YT' => __('Yukon','custom-registration-form-builder-with-submission-manager')
      );
    }

    public static function get_language() {
        return array(
            'Afar' => 'Afar',
            'Abkhaz' => 'Abkhaz',
            'Avestan' => 'Avestan',
            'Afrikaans' => 'Afrikaans',
            'Akan' => 'Akan',
            'Amharic' => 'Amharic',
            'Aragonese' => 'Aragonese',
            'Arabic' => 'Arabic',
            'Assamese' => 'Assamese',
            'Avaric' => 'Avaric',
            'Aymara' => 'Aymara',
            'Azerbaijani' => 'Azerbaijani',
            'Bashkir' => 'Bashkir',
            'Belarusian' => 'Belarusian',
            'Bulgarian' => 'Bulgarian',
            'Bihari' => 'Bihari',
            'Bislama' => 'Bislama',
            'Bambara' => 'Bambara',
            'Bengali' => 'Bengali',
            'Tibetan Standard, Tibetan, Central' => 'Tibetan Standard, Tibetan, Central',
            'Breton' => 'Breton',
            'Bosnian' => 'Bosnian',
            'Catalan' => 'Catalan',
            'Chechen' => 'Chechen',
            'Chamorro' => 'Chamorro',
            'Corsican' => 'Corsican',
            'Cree' => 'Cree',
            'Czech' => 'Czech',
            'Church Slavic' => 'Old Church Slavonic, Church Slavic, Church Slavonic, Old Bulgarian, Old Slavonic',
            'Chuvash' => 'Chuvash',
            'Welsh' => 'Welsh',
            'Danish' => 'Danish',
            'German' => 'German',
            'Divehi' => 'Divehi; Dhivehi; Maldivian;',
            'Dzongkha' => 'Dzongkha',
            'Ewe' => 'Ewe',
            'Greek' => 'Greek, Modern',
            'English' => 'English',
            'Esperanto' => 'Esperanto',
            'Spanish' => 'Spanish; Castilian',
            'Estonian' => 'Estonian',
            'Basque' => 'Basque',
            'Persian' => 'Persian',
            'Fula' => 'Fula; Fulah; Pulaar; Pular',
            'Finnish' => 'Finnish',
            'Fijian' => 'Fijian',
            'Faroese' => 'Faroese',
            'French' => 'French',
            'Western Frisian' => 'Western Frisian',
            'Irish' => 'Irish',
            'Gaelic' => 'Scottish Gaelic; Gaelic',
            'Galician' => 'Galician',
            'Guarana' => 'Guarana',
            'Gujarati' => 'Gujarati',
            'Manx' => 'Manx',
            'Hausa' => 'Hausa',
            'Hebrew' => 'Hebrew (modern)',
            'Hindi' => 'Hindi',
            'Hiri Motu' => 'Hiri Motu',
            'Croatian' => 'Croatian',
            'Haitian' => 'Haitian; Haitian Creole',
            'Hungarian' => 'Hungarian',
            'Armenian' => 'Armenian',
            'Herero' => 'Herero',
            'Interlingua' => 'Interlingua',
            'Indonesian' => 'Indonesian',
            'Interlingue' => 'Interlingue',
            'Igbo' => 'Igbo',
            'Nuosu' => 'Nuosu',
            'Inupiaq' => 'Inupiaq',
            'Ido' => 'Ido',
            'Icelandic' => 'Icelandic',
            'Italian' => 'Italian',
            'Inuktitut' => 'Inuktitut',
            'Japanese' => 'Japanese (ja)',
            'Javanese' => 'Javanese (jv)',
            'Georgian' => 'Georgian',
            'Kongo' => 'Kongo',
            'Kikuyu' => 'Kikuyu, Gikuyu',
            'Kwanyama' => 'Kwanyama, Kuanyama',
            'Kazakh' => 'Kazakh',
            'Kalaallisut' => 'Kalaallisut, Greenlandic',
            'Khmer' => 'Khmer',
            'Kannada' => 'Kannada',
            'Korean' => 'Korean',
            'Kanuri' => 'Kanuri',
            'Kashmiri' => 'Kashmiri',
            'Kurdish' => 'Kurdish',
            'Komi' => 'Komi',
            'Cornish' => 'Cornish',
            'Kinyarwanda' => 'Kinyarwanda',
            'Kirghiz' => 'Kirghiz, Kyrgyz',
            'Kirundi' => 'Kirundi',
            'Latin' => 'Latin',
            'Luxembourgish' => 'Luxembourgish, Letzeburgesch',
            'Luganda' => 'Luganda',
            'Limburgish' => 'Limburgish, Limburgan, Limburger',
            'Lingala' => 'Lingala',
            'Lao' => 'Lao',
            'Lithuanian' => 'Lithuanian',
            'Luba-Katanga' => 'Luba-Katanga',
            'Latvian' => 'Latvian',
            'Malagasy' => 'Malagasy',
            'Marshallese' => 'Marshallese',
            'Maori' => 'Maori',
            'Macedonian' => 'Macedonian',
            'Malayalam' => 'Malayalam',
            'Mongolian' => 'Mongolian',
            'Marathi' => 'Marathi (Mara?hi)',
            'Malay' => 'Malay',
            'Maltese' => 'Maltese',
            'Burmese' => 'Burmese',
            'Nauru' => 'Nauru',
            'Norwegian' => 'Norwegian',
            'North Ndebele' => 'North Ndebele',
            'Nepali' => 'Nepali',
            'Ndonga' => 'Ndonga',
            'Dutch' => 'Dutch',
            'Norwegian Nynorsk' => 'Norwegian Nynorsk',
            'Norwegian' => 'Norwegian',
            'South Ndebele' => 'South Ndebele',
            'Navajo' => 'Navajo, Navaho',
            'Chichewa' => 'Chichewa; Chewa; Nyanja',
            'Occitan' => 'Occitan',
            'Ojibwe' => 'Ojibwe, Ojibwa',
            'Oromo' => 'Oromo',
            'Oriya' => 'Oriya',
            'Ossetian' => 'Ossetian, Ossetic',
            'Panjabi' => 'Panjabi, Punjabi',
            'Pali' => 'Pali',
            'Polish' => 'Polish',
            'Pashto' => 'Pashto, Pushto',
            'Portuguese' => 'Portuguese',
            'Quechua' => 'Quechua',
            'Romansh' => 'Romansh',
            'Romanian' => 'Romanian, Moldavian, Moldovan',
            'Russian' => 'Russian',
            'Sanskrit' => 'Sanskrit',
            'Sardinian' => 'Sardinian',
            'Ukrainian' => 'Ukrainian'
        );
    }

    public static function get_countries() {
        $list = array(
            "Afghanistan[AF]" => __("Afghanistan","custom-registration-form-builder-with-submission-manager"),
            "Aland Islands[AX]" => __("Aland Islands","custom-registration-form-builder-with-submission-manager"),
            "Albania[AL]" => __("Albania","custom-registration-form-builder-with-submission-manager"),
            "Algeria[DZ]" => __("Algeria","custom-registration-form-builder-with-submission-manager"),
            "American Samoa[AS]" => __("American Samoa","custom-registration-form-builder-with-submission-manager"),
            "Andorra[AD]" => __("Andorra","custom-registration-form-builder-with-submission-manager"),
            "Angola[AO]" => __("Angola","custom-registration-form-builder-with-submission-manager"),
            "Anguilla[AI]" => __("Anguilla","custom-registration-form-builder-with-submission-manager"),
            "Antarctica[AQ]" => __("Antarctica","custom-registration-form-builder-with-submission-manager"),
            "Antigua and Barbuda[AG]" => __("Antigua and Barbuda","custom-registration-form-builder-with-submission-manager"),
            "Argentina[AR]" => __("Argentina","custom-registration-form-builder-with-submission-manager"),
            "Armenia[AM]" => __("Armenia","custom-registration-form-builder-with-submission-manager"),
            "Aruba[AW]" => __("Aruba","custom-registration-form-builder-with-submission-manager"),
            "Australia[AU]" => __("Australia","custom-registration-form-builder-with-submission-manager"),
            "Austria[AT]" => __("Austria","custom-registration-form-builder-with-submission-manager"),
            "Azerbaijan[AZ]" => __("Azerbaijan","custom-registration-form-builder-with-submission-manager"),
            "Bahamas, The[BS]" => __("Bahamas, The","custom-registration-form-builder-with-submission-manager"),
            "Bahrain[BH]" => __("Bahrain","custom-registration-form-builder-with-submission-manager"),
            "Bangladesh[BD]" => __("Bangladesh","custom-registration-form-builder-with-submission-manager"),
            "Barbados[BB]" => __("Barbados","custom-registration-form-builder-with-submission-manager"),
            "Belarus[BY]" => __("Belarus","custom-registration-form-builder-with-submission-manager"),
            "Belgium[BE]" => __("Belgium","custom-registration-form-builder-with-submission-manager"),
            "Belize[BZ]" => __("Belize","custom-registration-form-builder-with-submission-manager"),
            "Benin[BJ]" => __("Benin","custom-registration-form-builder-with-submission-manager"),
            "Bermuda[BM]" => __("Bermuda","custom-registration-form-builder-with-submission-manager"),
            "Bhutan[BT]" => __("Bhutan","custom-registration-form-builder-with-submission-manager"),
            "Bolivia[BO]" => __("Bolivia","custom-registration-form-builder-with-submission-manager"),
            "Bosnia and Herzegovina[BA]" => __("Bosnia and Herzegovina","custom-registration-form-builder-with-submission-manager"),
            "Botswana[BW]" => __("Botswana","custom-registration-form-builder-with-submission-manager"),
            "Bouvet Island[BV]" => __("Bouvet Island","custom-registration-form-builder-with-submission-manager"),
            "Brazil[BR]" => __("Brazil","custom-registration-form-builder-with-submission-manager"),
            "British Indian Ocean Territory[IO]" => __("British Indian Ocean Territory","custom-registration-form-builder-with-submission-manager"),
            "Brunei Darussalam[BN]" => __("Brunei Darussalam","custom-registration-form-builder-with-submission-manager"),
            "Bulgaria[BG]" => __("Bulgaria","custom-registration-form-builder-with-submission-manager"),
            "Burkina Faso[BF]" => __("Burkina Faso","custom-registration-form-builder-with-submission-manager"),
            "Burundi[BI]" => __("Burundi","custom-registration-form-builder-with-submission-manager"),
            "Cambodia[KH]" => __("Cambodia","custom-registration-form-builder-with-submission-manager"),
            "Cameroon[CM]" => __("Cameroon","custom-registration-form-builder-with-submission-manager"),
            "Canada[CA]" => __("Canada","custom-registration-form-builder-with-submission-manager"),
            "Cape Verde[CV]" => __("Cape Verde","custom-registration-form-builder-with-submission-manager"),
            "Cayman Islands[KY]" => __("Cayman Islands","custom-registration-form-builder-with-submission-manager"),
            "Central African Republic[CF]" => __("Central African Republic","custom-registration-form-builder-with-submission-manager"),
            "Chad[TD]" => __("Chad","custom-registration-form-builder-with-submission-manager"),
            "Chile[CL]" => __("Chile","custom-registration-form-builder-with-submission-manager"),
            "China[CN]" => __("China","custom-registration-form-builder-with-submission-manager"),
            "Christmas Island[CX]" => __("Christmas Island","custom-registration-form-builder-with-submission-manager"),
            "Cocos (Keeling) Islands[CC]" => __("Cocos (Keeling) Islands","custom-registration-form-builder-with-submission-manager"),
            "Colombia[CO]" => __("Colombia","custom-registration-form-builder-with-submission-manager"),
            "Comoros[KM]" => __("Comoros","custom-registration-form-builder-with-submission-manager"),
            "Congo[CG]" => __("Congo","custom-registration-form-builder-with-submission-manager"),
            "Congo, The Democratic Republic Of The[CD]" => __("Congo, The Democratic Republic Of The","custom-registration-form-builder-with-submission-manager"),
            "Cook Islands[CK]" => __("Cook Islands","custom-registration-form-builder-with-submission-manager"),
            "Costa Rica[CR]" => __("Costa Rica","custom-registration-form-builder-with-submission-manager"),
            "Cote D'ivoire[CI]" => __("Cote D'ivoire","custom-registration-form-builder-with-submission-manager"),
            "Croatia[HR]" => __("Croatia","custom-registration-form-builder-with-submission-manager"),
            "Cuba[CU]" => __("Cuba","custom-registration-form-builder-with-submission-manager"),
            "Cyprus[CY]" => __("Cyprus","custom-registration-form-builder-with-submission-manager"),
            "Czech Republic[CZ]" => __("Czech Republic","custom-registration-form-builder-with-submission-manager"),
            "Denmark[DK]" => __("Denmark","custom-registration-form-builder-with-submission-manager"),
            "Djibouti[DJ]" => __("Djibouti","custom-registration-form-builder-with-submission-manager"),
            "Dominica[DM]" => __("Dominica","custom-registration-form-builder-with-submission-manager"),
            "Dominican Republic[DO]" => __("Dominican Republic","custom-registration-form-builder-with-submission-manager"),
            "Ecuador[EC]" => __("Ecuador","custom-registration-form-builder-with-submission-manager"),
            "Egypt[EG]" => __("Egypt","custom-registration-form-builder-with-submission-manager"),
            "El Salvador[SV]" => __("El Salvador","custom-registration-form-builder-with-submission-manager"),
            "Equatorial Guinea[GQ]" => __("Equatorial Guinea","custom-registration-form-builder-with-submission-manager"),
            "Eritrea[ER]" => __("Eritrea","custom-registration-form-builder-with-submission-manager"),
            "Estonia[EE]" => __("Estonia","custom-registration-form-builder-with-submission-manager"),
            "Ethiopia[ET]" => __("Ethiopia","custom-registration-form-builder-with-submission-manager"),
            "Falkland Islands (Malvinas)[FK]" => __("Falkland Islands (Malvinas)","custom-registration-form-builder-with-submission-manager"),
            "Faroe Islands[FO]" => __("Faroe Islands","custom-registration-form-builder-with-submission-manager"),
            "Fiji[FJ]" => __("Fiji","custom-registration-form-builder-with-submission-manager"),
            "Finland[FI]" => __("Finland","custom-registration-form-builder-with-submission-manager"),
            "France[FR]" => __("France","custom-registration-form-builder-with-submission-manager"),
            "French Guiana[GF]" => __("French Guiana","custom-registration-form-builder-with-submission-manager"),
            "French Polynesia[PF]" => __("French Polynesia","custom-registration-form-builder-with-submission-manager"),
            "French Southern Territories[TF]" => __("French Southern Territories","custom-registration-form-builder-with-submission-manager"),
            "Gabon[GA]" => __("Gabon","custom-registration-form-builder-with-submission-manager"),
            "Gambia, The[GM]" => __("Gambia, The","custom-registration-form-builder-with-submission-manager"),
            "Georgia[GE]" => __("Georgia","custom-registration-form-builder-with-submission-manager"),
            "Germany[DE]" => __("Germany","custom-registration-form-builder-with-submission-manager"),
            "Ghana[GH]" => __("Ghana","custom-registration-form-builder-with-submission-manager"),
            "Gibraltar[GI]" => __("Gibraltar","custom-registration-form-builder-with-submission-manager"),
            "Greece[GR]" => __("Greece","custom-registration-form-builder-with-submission-manager"),
            "Greenland[GL]" => __("Greenland","custom-registration-form-builder-with-submission-manager"),
            "Grenada[GD]" => __("Grenada","custom-registration-form-builder-with-submission-manager"),
            "Guadeloupe[GP]" => __("Guadeloupe","custom-registration-form-builder-with-submission-manager"),
            "Guam[GU]" => __("Guam","custom-registration-form-builder-with-submission-manager"),
            "Guatemala[GT]" => __("Guatemala","custom-registration-form-builder-with-submission-manager"),
            "Guernsey[GG]" => __("Guernsey","custom-registration-form-builder-with-submission-manager"),
            "Guinea[GN]" => __("Guinea","custom-registration-form-builder-with-submission-manager"),
            "Guinea-Bissau[GW]" => __("Guinea-Bissau","custom-registration-form-builder-with-submission-manager"),
            "Guyana[GY]" => __("Guyana","custom-registration-form-builder-with-submission-manager"),
            "Haiti[HT]" => __("Haiti","custom-registration-form-builder-with-submission-manager"),
            "Heard Island and the McDonald Islands[HM]" => __("Heard Island and the McDonald Islands","custom-registration-form-builder-with-submission-manager"),
            "Holy See[VA]" => __("Holy See","custom-registration-form-builder-with-submission-manager"),
            "Honduras[HN]" => __("Honduras","custom-registration-form-builder-with-submission-manager"),
            "Hong Kong[HK]" => __("Hong Kong","custom-registration-form-builder-with-submission-manager"),
            "Hungary[HU]" => __("Hungary","custom-registration-form-builder-with-submission-manager"),
            "Iceland[IS]" => __("Iceland","custom-registration-form-builder-with-submission-manager"),
            "India[IN]" => __("India","custom-registration-form-builder-with-submission-manager"),
            "Indonesia[ID]" => __("Indonesia","custom-registration-form-builder-with-submission-manager"),
            "Iraq[IQ]" => __("Iraq","custom-registration-form-builder-with-submission-manager"),
            "Iran[IR]" => __("Iran","custom-registration-form-builder-with-submission-manager"),
            "Ireland[IE]" => __("Ireland","custom-registration-form-builder-with-submission-manager"),
            "Isle Of Man[IM]" => __("Isle Of Man","custom-registration-form-builder-with-submission-manager"),
            "Israel[IL]" => __("Israel","custom-registration-form-builder-with-submission-manager"),
            "Italy[IT]" => __("Italy","custom-registration-form-builder-with-submission-manager"),
            "Jamaica[JM]" => __("Jamaica","custom-registration-form-builder-with-submission-manager"),
            "Japan[JP]" => __("Japan","custom-registration-form-builder-with-submission-manager"),
            "Jersey[JE]" => __("Jersey","custom-registration-form-builder-with-submission-manager"),
            "Jordan[JO]" => __("Jordan","custom-registration-form-builder-with-submission-manager"),
            "Kazakhstan[KZ]" => __("Kazakhstan","custom-registration-form-builder-with-submission-manager"),
            "Kenya[KE]" => __("Kenya","custom-registration-form-builder-with-submission-manager"),
            "Kiribati[KI]" => __("Kiribati","custom-registration-form-builder-with-submission-manager"),
            "Korea, Republic Of[KR]" => __("Korea, Republic Of","custom-registration-form-builder-with-submission-manager"),
            "Kosovo[KS]" => __("Kosovo","custom-registration-form-builder-with-submission-manager"),
            "Kuwait[KW]" => __("Kuwait","custom-registration-form-builder-with-submission-manager"),
            "Kyrgyzstan[KG]" => __("Kyrgyzstan","custom-registration-form-builder-with-submission-manager"),
            "Lao People's Democratic Republic[LA]" => __("Lao People's Democratic Republic","custom-registration-form-builder-with-submission-manager"),
            "Latvia[LV]" => __("Latvia","custom-registration-form-builder-with-submission-manager"),
            "Lebanon[LB]" => __("Lebanon","custom-registration-form-builder-with-submission-manager"),
            "Lesotho[LS]" => __("Lesotho","custom-registration-form-builder-with-submission-manager"),
            "Liberia[LR]" => __("Liberia","custom-registration-form-builder-with-submission-manager"),
            "Libya[LY]" => __("Libya","custom-registration-form-builder-with-submission-manager"),
            "Liechtenstein[LI]" => __("Liechtenstein","custom-registration-form-builder-with-submission-manager"),
            "Lithuania[LT]" => __("Lithuania","custom-registration-form-builder-with-submission-manager"),
            "Luxembourg[LU]" => __("Luxembourg","custom-registration-form-builder-with-submission-manager"),
            "Macao[MO]" => __("Macao","custom-registration-form-builder-with-submission-manager"),
            "Macedonia, The Former Yugoslav Republic Of[MK]" => __("Macedonia, The Former Yugoslav Republic Of","custom-registration-form-builder-with-submission-manager"),
            "Madagascar[MG]" => __("Madagascar","custom-registration-form-builder-with-submission-manager"),
            "Malawi[MW]" => __("Malawi","custom-registration-form-builder-with-submission-manager"),
            "Malaysia[MY]" => __("Malaysia","custom-registration-form-builder-with-submission-manager"),
            "Maldives[MV]" => __("Maldives","custom-registration-form-builder-with-submission-manager"),
            "Mali[ML]" => __("Mali","custom-registration-form-builder-with-submission-manager"),
            "Malta[MT]" => __("Malta","custom-registration-form-builder-with-submission-manager"),
            "Marshall Islands[MH]" => __("Marshall Islands","custom-registration-form-builder-with-submission-manager"),
            "Martinique[MQ]" => __("Martinique","custom-registration-form-builder-with-submission-manager"),
            "Mauritania[MR]" => __("Mauritania","custom-registration-form-builder-with-submission-manager"),
            "Mauritius[MU]" => __("Mauritius","custom-registration-form-builder-with-submission-manager"),
            "Mayotte[YT]" => __("Mayotte","custom-registration-form-builder-with-submission-manager"),
            "Mexico[MX]" => __("Mexico","custom-registration-form-builder-with-submission-manager"),
            "Micronesia, Federated States Of[FM]" => __("Micronesia, Federated States Of","custom-registration-form-builder-with-submission-manager"),
            "Moldova, Republic Of[MD]" => __("Moldova, Republic Of","custom-registration-form-builder-with-submission-manager"),
            "Monaco[MC]" => __("Monaco","custom-registration-form-builder-with-submission-manager"),
            "Mongolia[MN]" => __("Mongolia","custom-registration-form-builder-with-submission-manager"),
            "Montenegro[ME]" => __("Montenegro","custom-registration-form-builder-with-submission-manager"),
            "Montserrat[MS]" => __("Montserrat","custom-registration-form-builder-with-submission-manager"),
            "Morocco[MA]" => __("Morocco","custom-registration-form-builder-with-submission-manager"),
            "Mozambique[MZ]" => __("Mozambique","custom-registration-form-builder-with-submission-manager"),
            "Myanmar[MM]" => __("Myanmar","custom-registration-form-builder-with-submission-manager"),
            "Namibia[NA]" => __("Namibia","custom-registration-form-builder-with-submission-manager"),
            "Nauru[NR]" => __("Nauru","custom-registration-form-builder-with-submission-manager"),
            "Nepal[NP]" => __("Nepal","custom-registration-form-builder-with-submission-manager"),
            "Netherlands[NL]" => __("Netherlands","custom-registration-form-builder-with-submission-manager"),
            "Netherlands Antilles[AN]" => __("Netherlands Antilles","custom-registration-form-builder-with-submission-manager"),
            "New Caledonia[NC]" => __("New Caledonia","custom-registration-form-builder-with-submission-manager"),
            "New Zealand[NZ]" => __("New Zealand","custom-registration-form-builder-with-submission-manager"),
            "Nicaragua[NI]" => __("Nicaragua","custom-registration-form-builder-with-submission-manager"),
            "Niger[NE]" => __("Niger","custom-registration-form-builder-with-submission-manager"),
            "Nigeria[NG]" => __("Nigeria","custom-registration-form-builder-with-submission-manager"),
            "Niue[NU]" => __("Niue","custom-registration-form-builder-with-submission-manager"),
            "Norfolk Island[NF]" => __("Norfolk Island","custom-registration-form-builder-with-submission-manager"),
            "Northern Mariana Islands[MP]" => __("Northern Mariana Islands","custom-registration-form-builder-with-submission-manager"),
            "Norway[NO]" => __("Norway","custom-registration-form-builder-with-submission-manager"),
            "Oman[OM]" => __("Oman","custom-registration-form-builder-with-submission-manager"),
            "Pakistan[PK]" => __("Pakistan","custom-registration-form-builder-with-submission-manager"),
            "Palau[PW]" => __("Palau","custom-registration-form-builder-with-submission-manager"),
            "Palestinian Territories[PS]" => __("Palestinian Territories","custom-registration-form-builder-with-submission-manager"),
            "Panama[PA]" => __("Panama","custom-registration-form-builder-with-submission-manager"),
            "Papua New Guinea[PG]" => __("Papua New Guinea","custom-registration-form-builder-with-submission-manager"),
            "Paraguay[PY]" => __("Paraguay","custom-registration-form-builder-with-submission-manager"),
            "Peru[PE]" => __("Peru","custom-registration-form-builder-with-submission-manager"),
            "Philippines[PH]" => __("Philippines","custom-registration-form-builder-with-submission-manager"),
            "Pitcairn[PN]" => __("Pitcairn","custom-registration-form-builder-with-submission-manager"),
            "Poland[PL]" => __("Poland","custom-registration-form-builder-with-submission-manager"),
            "Portugal[PT]" => __("Portugal","custom-registration-form-builder-with-submission-manager"),
            "Puerto Rico[PR]" => __("Puerto Rico","custom-registration-form-builder-with-submission-manager"),
            "Qatar[QA]" => __("Qatar","custom-registration-form-builder-with-submission-manager"),
            "Reunion[RE]" => __("Reunion","custom-registration-form-builder-with-submission-manager"),
            "Romania[RO]" => __("Romania","custom-registration-form-builder-with-submission-manager"),
            "Russian Federation[RU]" => __("Russian Federation","custom-registration-form-builder-with-submission-manager"),
            "Rwanda[RW]" => __("Rwanda","custom-registration-form-builder-with-submission-manager"),
            "Saint Barthelemy[BL]" => __("Saint Barthelemy","custom-registration-form-builder-with-submission-manager"),
            "Saint Helena[SH]" => __("Saint Helena","custom-registration-form-builder-with-submission-manager"),
            "Saint Kitts and Nevis[KN]" => __("Saint Kitts and Nevis","custom-registration-form-builder-with-submission-manager"),
            "Saint Lucia[LC]" => __("Saint Lucia","custom-registration-form-builder-with-submission-manager"),
            "Saint Martin[MF]" => __("Saint Martin","custom-registration-form-builder-with-submission-manager"),
            "Saint Pierre and Miquelon[PM]" => __("Saint Pierre and Miquelon","custom-registration-form-builder-with-submission-manager"),
            "Saint Vincent and The Grenadines[VC]" => __("Saint Vincent and The Grenadines","custom-registration-form-builder-with-submission-manager"),
            "Samoa[WS]" => __("Samoa","custom-registration-form-builder-with-submission-manager"),
            "San Marino[SM]" => __("San Marino","custom-registration-form-builder-with-submission-manager"),
            "Sao Tome and Principe[ST]" => __("Sao Tome and Principe","custom-registration-form-builder-with-submission-manager"),
            "Saudi Arabia[SA]" => __("Saudi Arabia","custom-registration-form-builder-with-submission-manager"),
            "Senegal[SN]" => __("Senegal","custom-registration-form-builder-with-submission-manager"),
            "Serbia[RS]" => __("Serbia","custom-registration-form-builder-with-submission-manager"),
            "Seychelles[SC]" => __("Seychelles","custom-registration-form-builder-with-submission-manager"),
            "Sierra Leone[SL]" => __("Sierra Leone","custom-registration-form-builder-with-submission-manager"),
            "Singapore[SG]" => __("Singapore","custom-registration-form-builder-with-submission-manager"),
            "Slovakia[SK]" => __("Slovakia","custom-registration-form-builder-with-submission-manager"),
            "Slovenia[SI]" => __("Slovenia","custom-registration-form-builder-with-submission-manager"),
            "Solomon Islands[SB]" => __("Solomon Islands","custom-registration-form-builder-with-submission-manager"),
            "Somalia[SO]" => __("Somalia","custom-registration-form-builder-with-submission-manager"),
            "South Africa[ZA]" => __("South Africa","custom-registration-form-builder-with-submission-manager"),
            "South Georgia and the South Sandwich Islands[GS]" => __("South Georgia and the South Sandwich Islands","custom-registration-form-builder-with-submission-manager"),
            "Spain[ES]" => __("Spain","custom-registration-form-builder-with-submission-manager"),
            "Sri Lanka[LK]" => __("Sri Lanka","custom-registration-form-builder-with-submission-manager"),
            "Sudan[SD]" => __("Sudan","custom-registration-form-builder-with-submission-manager"),
            "Suriname[SR]" => __("Suriname","custom-registration-form-builder-with-submission-manager"),
            "Svalbard and Jan Mayen[SJ]" => __("Svalbard and Jan Mayen","custom-registration-form-builder-with-submission-manager"),
            "Swaziland[SZ]" => __("Swaziland","custom-registration-form-builder-with-submission-manager"),
            "Sweden[SE]" => __("Sweden","custom-registration-form-builder-with-submission-manager"),
            "Switzerland[CH]" => __("Switzerland","custom-registration-form-builder-with-submission-manager"),
            "Syria[SY]" => __("Syria","custom-registration-form-builder-with-submission-manager"),
            "Taiwan[TW]" => __("Taiwan","custom-registration-form-builder-with-submission-manager"),
            "Tajikistan[TJ]" => __("Tajikistan","custom-registration-form-builder-with-submission-manager"),
            "Tanzania, United Republic Of[TZ]" => __("Tanzania, United Republic Of","custom-registration-form-builder-with-submission-manager"),
            "Thailand[TH]" => __("Thailand","custom-registration-form-builder-with-submission-manager"),
            "Timor-leste[TL]" => __("Timor-leste","custom-registration-form-builder-with-submission-manager"),
            "Togo[TG]" => __("Togo","custom-registration-form-builder-with-submission-manager"),
            "Tokelau[TK]" => __("Tokelau","custom-registration-form-builder-with-submission-manager"),
            "Tonga[TO]" => __("Tonga","custom-registration-form-builder-with-submission-manager"),
            "Trinidad and Tobago[TT]" => __("Trinidad and Tobago","custom-registration-form-builder-with-submission-manager"),
            "Tunisia[TN]" => __("Tunisia","custom-registration-form-builder-with-submission-manager"),
            "Turkey[TR]" => __("Turkey","custom-registration-form-builder-with-submission-manager"),
            "Turkmenistan[TM]" => __("Turkmenistan","custom-registration-form-builder-with-submission-manager"),
            "Turks and Caicos Islands[TC]" => __("Turks and Caicos Islands","custom-registration-form-builder-with-submission-manager"),
            "Tuvalu[TV]" => __("Tuvalu","custom-registration-form-builder-with-submission-manager"),
            "Uganda[UG]" => __("Uganda","custom-registration-form-builder-with-submission-manager"),
            "Ukraine[UA]" => __("Ukraine","custom-registration-form-builder-with-submission-manager"),
            "United Arab Emirates[AE]" => __("United Arab Emirates","custom-registration-form-builder-with-submission-manager"),
            "United Kingdom[GB]" => __("United Kingdom","custom-registration-form-builder-with-submission-manager"),
            "United States[US]" => __("United States","custom-registration-form-builder-with-submission-manager"),
            "United States Minor Outlying Islands[UM]" => __("United States Minor Outlying Islands","custom-registration-form-builder-with-submission-manager"),
            "Uruguay[UY]" => __("Uruguay","custom-registration-form-builder-with-submission-manager"),
            "Uzbekistan[UZ]" => __("Uzbekistan","custom-registration-form-builder-with-submission-manager"),
            "Vanuatu[VU]" => __("Vanuatu","custom-registration-form-builder-with-submission-manager"),
            "Venezuela[VE]" => __("Venezuela","custom-registration-form-builder-with-submission-manager"),
            "Vietnam[VN]" => __("Vietnam","custom-registration-form-builder-with-submission-manager"),
            "Virgin Islands, British[VG]" => __("Virgin Islands, British","custom-registration-form-builder-with-submission-manager"),
            "Virgin Islands, U.S.[VI]" => __("Virgin Islands, U.S.","custom-registration-form-builder-with-submission-manager"),
            "Wallis and Futuna[WF]" => __("Wallis and Futuna","custom-registration-form-builder-with-submission-manager"),
            "Western Sahara[EH]" => __("Western Sahara","custom-registration-form-builder-with-submission-manager"),
            "Yemen[YE]" => __("Yemen","custom-registration-form-builder-with-submission-manager"),
            "Zambia[ZM]" => __("Zambia","custom-registration-form-builder-with-submission-manager"),
            "Zimbabwe[ZW]" => __("Zimbabwe","custom-registration-form-builder-with-submission-manager")        
          );
          $list = apply_filters('rm_country_list',$list);
          setlocale(LC_COLLATE, get_locale().'.UTF-8');
          asort($list, SORT_LOCALE_STRING);
          $def_element = array(null => RM_UI_Strings::get("LABEL_SELECT_COUNTRY"));
          return array_merge($def_element, $list);
    }

    public static function get_timezones() {
        $timezones = array(
            null => __('--Select Timezone--','custom-registration-form-builder-with-submission-manager'),
            'Africa/Abidjan' => __('Abidjan','custom-registration-form-builder-with-submission-manager'),
            'Africa/Accra' => __('Accra','custom-registration-form-builder-with-submission-manager'),
            'Africa/Addis_Ababa' => __('Addis Ababa','custom-registration-form-builder-with-submission-manager'),
            'Africa/Algiers' => __('Algiers','custom-registration-form-builder-with-submission-manager'),
            'Africa/Asmara' => __('Asmara','custom-registration-form-builder-with-submission-manager'),
            'Africa/Bamako' => __('Bamako','custom-registration-form-builder-with-submission-manager'),
            'Africa/Bangui' => __('Bangui','custom-registration-form-builder-with-submission-manager'),
            'Africa/Banjul' => __('Banjul','custom-registration-form-builder-with-submission-manager'),
            'Africa/Bissau' => __('Bissau','custom-registration-form-builder-with-submission-manager'),
            'Africa/Blantyre' => __('Blantyre','custom-registration-form-builder-with-submission-manager'),
            'Africa/Brazzaville' => __('Brazzaville','custom-registration-form-builder-with-submission-manager'),
            'Africa/Bujumbura' => __('Bujumbura','custom-registration-form-builder-with-submission-manager'),
            'Africa/Cairo' => __('Cairo','custom-registration-form-builder-with-submission-manager'),
            'Africa/Casablanca' => __('Casablanca','custom-registration-form-builder-with-submission-manager'),
            'Africa/Ceuta' => __('Ceuta','custom-registration-form-builder-with-submission-manager'),
            'Africa/Conakry' => __('Conakry','custom-registration-form-builder-with-submission-manager'),
            'Africa/Dakar' => __('Dakar','custom-registration-form-builder-with-submission-manager'),
            'Africa/Dar_es_Salaam' => __('Dar es Salaam','custom-registration-form-builder-with-submission-manager'),
            'Africa/Djibouti' => __('Djibouti','custom-registration-form-builder-with-submission-manager'),
            'Africa/Douala' => __('Douala','custom-registration-form-builder-with-submission-manager'),
            'Africa/El_Aaiun' => __('El Aaiun','custom-registration-form-builder-with-submission-manager'),
            'Africa/Freetown' => __('Freetown','custom-registration-form-builder-with-submission-manager'),
            'Africa/Gaborone' => __('Gaborone','custom-registration-form-builder-with-submission-manager'),
            'Africa/Harare' => __('Harare','custom-registration-form-builder-with-submission-manager'),
            'Africa/Johannesburg' => __('Johannesburg','custom-registration-form-builder-with-submission-manager'),
            'Africa/Juba' => __('Juba','custom-registration-form-builder-with-submission-manager'),
            'Africa/Kampala' => __('Kampala','custom-registration-form-builder-with-submission-manager'),
            'Africa/Khartoum' => __('Khartoum','custom-registration-form-builder-with-submission-manager'),
            'Africa/Kigali' => __('Kigali','custom-registration-form-builder-with-submission-manager'),
            'Africa/Kinshasa' => __('Kinshasa','custom-registration-form-builder-with-submission-manager'),
            'Africa/Lagos' => __('Lagos','custom-registration-form-builder-with-submission-manager'),
            'Africa/Libreville' => __('Libreville','custom-registration-form-builder-with-submission-manager'),
            'Africa/Lome' => __('Lome','custom-registration-form-builder-with-submission-manager'),
            'Africa/Luanda' => __('Luanda','custom-registration-form-builder-with-submission-manager'),
            'Africa/Lubumbashi' => __('Lubumbashi','custom-registration-form-builder-with-submission-manager'),
            'Africa/Lusaka' => __('Lusaka','custom-registration-form-builder-with-submission-manager'),
            'Africa/Malabo' => __('Malabo','custom-registration-form-builder-with-submission-manager'),
            'Africa/Maputo' => __('Maputo','custom-registration-form-builder-with-submission-manager'),
            'Africa/Maseru' => __('Maseru','custom-registration-form-builder-with-submission-manager'),
            'Africa/Mbabane' => __('Mbabane','custom-registration-form-builder-with-submission-manager'),
            'Africa/Mogadishu' => __('Mogadishu','custom-registration-form-builder-with-submission-manager'),
            'Africa/Monrovia' => __('Monrovia','custom-registration-form-builder-with-submission-manager'),
            'Africa/Nairobi' => __('Nairobi','custom-registration-form-builder-with-submission-manager'),
            'Africa/Ndjamena' => __('Ndjamena','custom-registration-form-builder-with-submission-manager'),
            'Africa/Niamey' => __('Niamey','custom-registration-form-builder-with-submission-manager'),
            'Africa/Nouakchott' => __('Nouakchott','custom-registration-form-builder-with-submission-manager'),
            'Africa/Ouagadougou' => __('Ouagadougou','custom-registration-form-builder-with-submission-manager'),
            'Africa/Porto-Novo' => __('Porto-Novo','custom-registration-form-builder-with-submission-manager'),
            'Africa/Sao_Tome' => __('Sao Tome','custom-registration-form-builder-with-submission-manager'),
            'Africa/Tripoli' => __('Tripoli','custom-registration-form-builder-with-submission-manager'),
            'Africa/Tunis' => __('Tunis','custom-registration-form-builder-with-submission-manager'),
            'Africa/Windhoek' => __('Windhoek','custom-registration-form-builder-with-submission-manager'),
            'America/Adak' => __('Adak','custom-registration-form-builder-with-submission-manager'),
            'America/Anchorage' => __('Anchorage','custom-registration-form-builder-with-submission-manager'),
            'America/Anguilla' => __('Anguilla','custom-registration-form-builder-with-submission-manager'),
            'America/Antigua' => __('Antigua','custom-registration-form-builder-with-submission-manager'),
            'America/Araguaina' => __('Araguaina','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/Buenos_Aires' => __('Argentina - Buenos Aires','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/Catamarca' => __('Argentina - Catamarca','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/Cordoba' => __('Argentina - Cordoba','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/Jujuy' => __('Argentina - Jujuy','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/La_Rioja' => __('Argentina - La Rioja','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/Mendoza' => __('Argentina - Mendoza','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/Rio_Gallegos' => __('Argentina - Rio Gallegos','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/Salta' => __('Argentina - Salta','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/San_Juan' => __('Argentina - San Juan','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/San_Luis' => __('Argentina - San Luis','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/Tucuman' => __('Argentina - Tucuman','custom-registration-form-builder-with-submission-manager'),
            'America/Argentina/Ushuaia' => __('Argentina - Ushuaia','custom-registration-form-builder-with-submission-manager'),
            'America/Aruba' => __('Aruba','custom-registration-form-builder-with-submission-manager'),
            'America/Asuncion' => __('Asuncion','custom-registration-form-builder-with-submission-manager'),
            'America/Atikokan' => __('Atikokan','custom-registration-form-builder-with-submission-manager'),
            'America/Bahia' => __('Bahia','custom-registration-form-builder-with-submission-manager'),
            'America/Bahia_Banderas' => __('Bahia Banderas','custom-registration-form-builder-with-submission-manager'),
            'America/Barbados' => __('Barbados','custom-registration-form-builder-with-submission-manager'),
            'America/Belem' => __('Belem','custom-registration-form-builder-with-submission-manager'),
            'America/Belize' => __('Belize','custom-registration-form-builder-with-submission-manager'),
            'America/Blanc-Sablon' => __('Blanc-Sablon','custom-registration-form-builder-with-submission-manager'),
            'America/Boa_Vista' => __('Boa Vista','custom-registration-form-builder-with-submission-manager'),
            'America/Bogota' => __('Bogota','custom-registration-form-builder-with-submission-manager'),
            'America/Boise' => __('Boise','custom-registration-form-builder-with-submission-manager'),
            'America/Cambridge_Bay' => __('Cambridge Bay','custom-registration-form-builder-with-submission-manager'),
            'America/Campo_Grande' => __('Campo Grande','custom-registration-form-builder-with-submission-manager'),
            'America/Cancun' => __('Cancun','custom-registration-form-builder-with-submission-manager'),
            'America/Caracas' => __('Caracas','custom-registration-form-builder-with-submission-manager'),
            'America/Cayenne' => __('Cayenne','custom-registration-form-builder-with-submission-manager'),
            'America/Cayman' => __('Cayman','custom-registration-form-builder-with-submission-manager'),
            'America/Chicago' => __('Chicago','custom-registration-form-builder-with-submission-manager'),
            'America/Chihuahua' => __('Chihuahua','custom-registration-form-builder-with-submission-manager'),
            'America/Costa_Rica' => __('Costa Rica','custom-registration-form-builder-with-submission-manager'),
            'America/Creston' => __('Creston','custom-registration-form-builder-with-submission-manager'),
            'America/Cuiaba' => __('Cuiaba','custom-registration-form-builder-with-submission-manager'),
            'America/Curacao' => __('Curacao','custom-registration-form-builder-with-submission-manager'),
            'America/Danmarkshavn' => __('Danmarkshavn','custom-registration-form-builder-with-submission-manager'),
            'America/Dawson' => __('Dawson','custom-registration-form-builder-with-submission-manager'),
            'America/Dawson_Creek' => __('Dawson Creek','custom-registration-form-builder-with-submission-manager'),
            'America/Denver' => __('Denver','custom-registration-form-builder-with-submission-manager'),
            'America/Detroit' => __('Detroit','custom-registration-form-builder-with-submission-manager'),
            'America/Dominica' => __('Dominica','custom-registration-form-builder-with-submission-manager'),
            'America/Edmonton' => __('Edmonton','custom-registration-form-builder-with-submission-manager'),
            'America/Eirunepe' => __('Eirunepe','custom-registration-form-builder-with-submission-manager'),
            'America/El_Salvador' => __('El Salvador','custom-registration-form-builder-with-submission-manager'),
            'America/Fortaleza' => __('Fortaleza','custom-registration-form-builder-with-submission-manager'),
            'America/Glace_Bay' => __('Glace Bay','custom-registration-form-builder-with-submission-manager'),
            'America/Godthab' => __('Godthab','custom-registration-form-builder-with-submission-manager'),
            'America/Goose_Bay' => __('Goose Bay','custom-registration-form-builder-with-submission-manager'),
            'America/Grand_Turk' => __('Grand Turk','custom-registration-form-builder-with-submission-manager'),
            'America/Grenada' => __('Grenada','custom-registration-form-builder-with-submission-manager'),
            'America/Guadeloupe' => __('Guadeloupe','custom-registration-form-builder-with-submission-manager'),
            'America/Guatemala' => __('Guatemala','custom-registration-form-builder-with-submission-manager'),
            'America/Guayaquil' => __('Guayaquil','custom-registration-form-builder-with-submission-manager'),
            'America/Guyana' => __('Guyana','custom-registration-form-builder-with-submission-manager'),
            'America/Halifax' => __('Halifax','custom-registration-form-builder-with-submission-manager'),
            'America/Havana' => __('Havana','custom-registration-form-builder-with-submission-manager'),
            'America/Hermosillo' => __('Hermosillo','custom-registration-form-builder-with-submission-manager'),
            'America/Indiana/Indianapolis' => __('Indiana - Indianapolis','custom-registration-form-builder-with-submission-manager'),
            'America/Indiana/Knox' => __('Indiana - Knox','custom-registration-form-builder-with-submission-manager'),
            'America/Indiana/Marengo' => __('Indiana - Marengo','custom-registration-form-builder-with-submission-manager'),
            'America/Indiana/Petersburg' => __('Indiana - Petersburg','custom-registration-form-builder-with-submission-manager'),
            'America/Indiana/Tell_City' => __('Indiana - Tell City','custom-registration-form-builder-with-submission-manager'),
            'America/Indiana/Vevay' => __('Indiana - Vevay','custom-registration-form-builder-with-submission-manager'),
            'America/Indiana/Vincennes' => __('Indiana - Vincennes','custom-registration-form-builder-with-submission-manager'),
            'America/Indiana/Winamac' => __('Indiana - Winamac','custom-registration-form-builder-with-submission-manager'),
            'America/Inuvik' => __('Inuvik','custom-registration-form-builder-with-submission-manager'),
            'America/Iqaluit' => __('Iqaluit','custom-registration-form-builder-with-submission-manager'),
            'America/Jamaica' => __('Jamaica','custom-registration-form-builder-with-submission-manager'),
            'America/Juneau' => __('Juneau','custom-registration-form-builder-with-submission-manager'),
            'America/Kentucky/Louisville' => __('Kentucky - Louisville','custom-registration-form-builder-with-submission-manager'),
            'America/Kentucky/Monticello' => __('Kentucky - Monticello','custom-registration-form-builder-with-submission-manager'),
            'America/Kralendijk' => __('Kralendijk','custom-registration-form-builder-with-submission-manager'),
            'America/La_Paz' => __('La Paz','custom-registration-form-builder-with-submission-manager'),
            'America/Lima' => __('Lima','custom-registration-form-builder-with-submission-manager'),
            'America/Los_Angeles' => __('Los Angeles','custom-registration-form-builder-with-submission-manager'),
            'America/Lower_Princes' => __('Lower Princes','custom-registration-form-builder-with-submission-manager'),
            'America/Maceio' => __('Maceio','custom-registration-form-builder-with-submission-manager'),
            'America/Managua' => __('Managua','custom-registration-form-builder-with-submission-manager'),
            'America/Manaus' => __('Manaus','custom-registration-form-builder-with-submission-manager'),
            'America/Marigot' => __('Marigot','custom-registration-form-builder-with-submission-manager'),
            'America/Martinique' => __('Martinique','custom-registration-form-builder-with-submission-manager'),
            'America/Matamoros' => __('Matamoros','custom-registration-form-builder-with-submission-manager'),
            'America/Mazatlan' => __('Mazatlan','custom-registration-form-builder-with-submission-manager'),
            'America/Menominee' => __('Menominee','custom-registration-form-builder-with-submission-manager'),
            'America/Merida' => __('Merida','custom-registration-form-builder-with-submission-manager'),
            'America/Metlakatla' => __('Metlakatla','custom-registration-form-builder-with-submission-manager'),
            'America/Mexico_City' => __('Mexico City','custom-registration-form-builder-with-submission-manager'),
            'America/Miquelon' => __('Miquelon','custom-registration-form-builder-with-submission-manager'),
            'America/Moncton' => __('Moncton','custom-registration-form-builder-with-submission-manager'),
            'America/Monterrey' => __('Monterrey','custom-registration-form-builder-with-submission-manager'),
            'America/Montevideo' => __('Montevideo','custom-registration-form-builder-with-submission-manager'),
            'America/Montserrat' => __('Montserrat','custom-registration-form-builder-with-submission-manager'),
            'America/Nassau' => __('Nassau','custom-registration-form-builder-with-submission-manager'),
            'America/New_York' => __('New York','custom-registration-form-builder-with-submission-manager'),
            'America/Nipigon' => __('Nipigon','custom-registration-form-builder-with-submission-manager'),
            'America/Nome' => __('Nome','custom-registration-form-builder-with-submission-manager'),
            'America/Noronha' => __('Noronha','custom-registration-form-builder-with-submission-manager'),
            'America/North_Dakota/Beulah' => __('North Dakota - Beulah','custom-registration-form-builder-with-submission-manager'),
            'America/North_Dakota/Center' => __('North Dakota - Center','custom-registration-form-builder-with-submission-manager'),
            'America/North_Dakota/New_Salem' => __('North Dakota - New Salem','custom-registration-form-builder-with-submission-manager'),
            'America/Ojinaga' => __('Ojinaga','custom-registration-form-builder-with-submission-manager'),
            'America/Panama' => __('Panama','custom-registration-form-builder-with-submission-manager'),
            'America/Pangnirtung' => __('Pangnirtung','custom-registration-form-builder-with-submission-manager'),
            'America/Paramaribo' => __('Paramaribo','custom-registration-form-builder-with-submission-manager'),
            'America/Phoenix' => __('Phoenix','custom-registration-form-builder-with-submission-manager'),
            'America/Port-au-Prince' => __('Port-au-Prince','custom-registration-form-builder-with-submission-manager'),
            'America/Port_of_Spain' => __('Port of Spain','custom-registration-form-builder-with-submission-manager'),
            'America/Porto_Velho' => __('Porto Velho','custom-registration-form-builder-with-submission-manager'),
            'America/Puerto_Rico' => __('Puerto Rico','custom-registration-form-builder-with-submission-manager'),
            'America/Rainy_River' => __('Rainy River','custom-registration-form-builder-with-submission-manager'),
            'America/Rankin_Inlet' => __('Rankin Inlet','custom-registration-form-builder-with-submission-manager'),
            'America/Recife' => __('Recife','custom-registration-form-builder-with-submission-manager'),
            'America/Regina' => __('Regina','custom-registration-form-builder-with-submission-manager'),
            'America/Resolute' => __('Resolute','custom-registration-form-builder-with-submission-manager'),
            'America/Rio_Branco' => __('Rio Branco','custom-registration-form-builder-with-submission-manager'),
            'America/Santa_Isabel' => __('Santa Isabel','custom-registration-form-builder-with-submission-manager'),
            'America/Santarem' => __('Santarem','custom-registration-form-builder-with-submission-manager'),
            'America/Santiago' => __('Santiago','custom-registration-form-builder-with-submission-manager'),
            'America/Santo_Domingo' => __('Santo Domingo','custom-registration-form-builder-with-submission-manager'),
            'America/Sao_Paulo' => __('Sao Paulo','custom-registration-form-builder-with-submission-manager'),
            'America/Scoresbysund' => __('Scoresbysund','custom-registration-form-builder-with-submission-manager'),
            'America/Sitka' => __('Sitka','custom-registration-form-builder-with-submission-manager'),
            'America/St_Barthelemy' => __('St Barthelemy','custom-registration-form-builder-with-submission-manager'),
            'America/St_Johns' => __('St Johns','custom-registration-form-builder-with-submission-manager'),
            'America/St_Kitts' => __('St Kitts','custom-registration-form-builder-with-submission-manager'),
            'America/St_Lucia' => __('St Lucia','custom-registration-form-builder-with-submission-manager'),
            'America/St_Thomas' => __('St Thomas','custom-registration-form-builder-with-submission-manager'),
            'America/St_Vincent' => __('St Vincent','custom-registration-form-builder-with-submission-manager'),
            'America/Swift_Current' => __('Swift Current','custom-registration-form-builder-with-submission-manager'),
            'America/Tegucigalpa' => __('Tegucigalpa','custom-registration-form-builder-with-submission-manager'),
            'America/Thule' => __('Thule','custom-registration-form-builder-with-submission-manager'),
            'America/Thunder_Bay' => __('Thunder Bay','custom-registration-form-builder-with-submission-manager'),
            'America/Tijuana' => __('Tijuana','custom-registration-form-builder-with-submission-manager'),
            'America/Toronto' => __('Toronto','custom-registration-form-builder-with-submission-manager'),
            'America/Tortola' => __('Tortola','custom-registration-form-builder-with-submission-manager'),
            'America/Vancouver' => __('Vancouver','custom-registration-form-builder-with-submission-manager'),
            'America/Whitehorse' => __('Whitehorse','custom-registration-form-builder-with-submission-manager'),
            'America/Winnipeg' => __('Winnipeg','custom-registration-form-builder-with-submission-manager'),
            'America/Yakutat' => __('Yakutat','custom-registration-form-builder-with-submission-manager'),
            'America/Yellowknife' => __('Yellowknife','custom-registration-form-builder-with-submission-manager'),
            'Antarctica/Casey' => __('Casey','custom-registration-form-builder-with-submission-manager'),
            'Antarctica/Davis' => __('Davis','custom-registration-form-builder-with-submission-manager'),
            'Antarctica/DumontDUrville' => __('DumontDUrville','custom-registration-form-builder-with-submission-manager'),
            'Antarctica/Macquarie' => __('Macquarie','custom-registration-form-builder-with-submission-manager'),
            'Antarctica/Mawson' => __('Mawson','custom-registration-form-builder-with-submission-manager'),
            'Antarctica/McMurdo' => __('McMurdo','custom-registration-form-builder-with-submission-manager'),
            'Antarctica/Palmer' => __('Palmer','custom-registration-form-builder-with-submission-manager'),
            'Antarctica/Rothera' => __('Rothera','custom-registration-form-builder-with-submission-manager'),
            'Antarctica/Syowa' => __('Syowa','custom-registration-form-builder-with-submission-manager'),
            'Antarctica/Troll' => __('Troll','custom-registration-form-builder-with-submission-manager'),
            'Antarctica/Vostok' => __('Vostok','custom-registration-form-builder-with-submission-manager'),
            'Arctic/Longyearbyen' => __('Longyearbyen','custom-registration-form-builder-with-submission-manager'),
            'Asia/Aden' => __('Aden','custom-registration-form-builder-with-submission-manager'),
            'Asia/Almaty' => __('Almaty','custom-registration-form-builder-with-submission-manager'),
            'Asia/Amman' => __('Amman','custom-registration-form-builder-with-submission-manager'),
            'Asia/Anadyr' => __('Anadyr','custom-registration-form-builder-with-submission-manager'),
            'Asia/Aqtau' => __('Aqtau','custom-registration-form-builder-with-submission-manager'),
            'Asia/Aqtobe' => __('Aqtobe','custom-registration-form-builder-with-submission-manager'),
            'Asia/Ashgabat' => __('Ashgabat','custom-registration-form-builder-with-submission-manager'),
            'Asia/Baghdad' => __('Baghdad','custom-registration-form-builder-with-submission-manager'),
            'Asia/Bahrain' => __('Bahrain','custom-registration-form-builder-with-submission-manager'),
            'Asia/Baku' => __('Baku','custom-registration-form-builder-with-submission-manager'),
            'Asia/Bangkok' => __('Bangkok','custom-registration-form-builder-with-submission-manager'),
            'Asia/Beirut' => __('Beirut','custom-registration-form-builder-with-submission-manager'),
            'Asia/Bishkek' => __('Bishkek','custom-registration-form-builder-with-submission-manager'),
            'Asia/Brunei' => __('Brunei','custom-registration-form-builder-with-submission-manager'),
            'Asia/Choibalsan' => __('Choibalsan','custom-registration-form-builder-with-submission-manager'),
            'Asia/Chongqing' => __('Chongqing','custom-registration-form-builder-with-submission-manager'),
            'Asia/Colombo' => __('Colombo','custom-registration-form-builder-with-submission-manager'),
            'Asia/Damascus' => __('Damascus','custom-registration-form-builder-with-submission-manager'),
            'Asia/Dhaka' => __('Dhaka','custom-registration-form-builder-with-submission-manager'),
            'Asia/Dili' => __('Dili','custom-registration-form-builder-with-submission-manager'),
            'Asia/Dubai' => __('Dubai','custom-registration-form-builder-with-submission-manager'),
            'Asia/Dushanbe' => __('Dushanbe','custom-registration-form-builder-with-submission-manager'),
            'Asia/Gaza' => __('Gaza','custom-registration-form-builder-with-submission-manager'),
            'Asia/Harbin' => __('Harbin','custom-registration-form-builder-with-submission-manager'),
            'Asia/Hebron' => __('Hebron','custom-registration-form-builder-with-submission-manager'),
            'Asia/Ho_Chi_Minh' => __('Ho Chi Minh','custom-registration-form-builder-with-submission-manager'),
            'Asia/Hong_Kong' => __('Hong Kong','custom-registration-form-builder-with-submission-manager'),
            'Asia/Hovd' => __('Hovd','custom-registration-form-builder-with-submission-manager'),
            'Asia/Irkutsk' => __('Irkutsk','custom-registration-form-builder-with-submission-manager'),
            'Asia/Jakarta' => __('Jakarta','custom-registration-form-builder-with-submission-manager'),
            'Asia/Jayapura' => __('Jayapura','custom-registration-form-builder-with-submission-manager'),
            'Asia/Jerusalem' => __('Jerusalem','custom-registration-form-builder-with-submission-manager'),
            'Asia/Kabul' => __('Kabul','custom-registration-form-builder-with-submission-manager'),
            'Asia/Kamchatka' => __('Kamchatka','custom-registration-form-builder-with-submission-manager'),
            'Asia/Karachi' => __('Karachi','custom-registration-form-builder-with-submission-manager'),
            'Asia/Kashgar' => __('Kashgar','custom-registration-form-builder-with-submission-manager'),
            'Asia/Kathmandu' => __('Kathmandu','custom-registration-form-builder-with-submission-manager'),
            'Asia/Khandyga' => __('Khandyga','custom-registration-form-builder-with-submission-manager'),
            'Asia/Kolkata' => __('Kolkata','custom-registration-form-builder-with-submission-manager'),
            'Asia/Krasnoyarsk' => __('Krasnoyarsk','custom-registration-form-builder-with-submission-manager'),
            'Asia/Kuala_Lumpur' => __('Kuala Lumpur','custom-registration-form-builder-with-submission-manager'),
            'Asia/Kuching' => __('Kuching','custom-registration-form-builder-with-submission-manager'),
            'Asia/Kuwait' => __('Kuwait','custom-registration-form-builder-with-submission-manager'),
            'Asia/Macau' => __('Macau','custom-registration-form-builder-with-submission-manager'),
            'Asia/Magadan' => __('Magadan','custom-registration-form-builder-with-submission-manager'),
            'Asia/Makassar' => __('Makassar','custom-registration-form-builder-with-submission-manager'),
            'Asia/Manila' => __('Manila','custom-registration-form-builder-with-submission-manager'),
            'Asia/Muscat' => __('Muscat','custom-registration-form-builder-with-submission-manager'),
            'Asia/Nicosia' => __('Nicosia','custom-registration-form-builder-with-submission-manager'),
            'Asia/Novokuznetsk' => __('Novokuznetsk','custom-registration-form-builder-with-submission-manager'),
            'Asia/Novosibirsk' => __('Novosibirsk','custom-registration-form-builder-with-submission-manager'),
            'Asia/Omsk' => __('Omsk','custom-registration-form-builder-with-submission-manager'),
            'Asia/Oral' => __('Oral','custom-registration-form-builder-with-submission-manager'),
            'Asia/Phnom_Penh' => __('Phnom Penh','custom-registration-form-builder-with-submission-manager'),
            'Asia/Pontianak' => __('Pontianak','custom-registration-form-builder-with-submission-manager'),
            'Asia/Pyongyang' => __('Pyongyang','custom-registration-form-builder-with-submission-manager'),
            'Asia/Qatar' => __('Qatar','custom-registration-form-builder-with-submission-manager'),
            'Asia/Qyzylorda' => __('Qyzylorda','custom-registration-form-builder-with-submission-manager'),
            'Asia/Rangoon' => __('Rangoon','custom-registration-form-builder-with-submission-manager'),
            'Asia/Riyadh' => __('Riyadh','custom-registration-form-builder-with-submission-manager'),
            'Asia/Sakhalin' => __('Sakhalin','custom-registration-form-builder-with-submission-manager'),
            'Asia/Samarkand' => __('Samarkand','custom-registration-form-builder-with-submission-manager'),
            'Asia/Seoul' => __('Seoul','custom-registration-form-builder-with-submission-manager'),
            'Asia/Shanghai' => __('Shanghai','custom-registration-form-builder-with-submission-manager'),
            'Asia/Singapore' => __('Singapore','custom-registration-form-builder-with-submission-manager'),
            'Asia/Taipei' => __('Taipei','custom-registration-form-builder-with-submission-manager'),
            'Asia/Tashkent' => __('Tashkent','custom-registration-form-builder-with-submission-manager'),
            'Asia/Tbilisi' => __('Tbilisi','custom-registration-form-builder-with-submission-manager'),
            'Asia/Tehran' => __('Tehran','custom-registration-form-builder-with-submission-manager'),
            'Asia/Thimphu' => __('Thimphu','custom-registration-form-builder-with-submission-manager'),
            'Asia/Tokyo' => __('Tokyo','custom-registration-form-builder-with-submission-manager'),
            'Asia/Ulaanbaatar' => __('Ulaanbaatar','custom-registration-form-builder-with-submission-manager'),
            'Asia/Urumqi' => __('Urumqi','custom-registration-form-builder-with-submission-manager'),
            'Asia/Ust-Nera' => __('Ust-Nera','custom-registration-form-builder-with-submission-manager'),
            'Asia/Vientiane' => __('Vientiane','custom-registration-form-builder-with-submission-manager'),
            'Asia/Vladivostok' => __('Vladivostok','custom-registration-form-builder-with-submission-manager'),
            'Asia/Yakutsk' => __('Yakutsk','custom-registration-form-builder-with-submission-manager'),
            'Asia/Yekaterinburg' => __('Yekaterinburg','custom-registration-form-builder-with-submission-manager'),
            'Asia/Yerevan' => __('Yerevan','custom-registration-form-builder-with-submission-manager'),
            'Atlantic/Azores' => __('Azores','custom-registration-form-builder-with-submission-manager'),
            'Atlantic/Bermuda' => __('Bermuda','custom-registration-form-builder-with-submission-manager'),
            'Atlantic/Canary' => __('Canary','custom-registration-form-builder-with-submission-manager'),
            'Atlantic/Cape_Verde' => __('Cape Verde','custom-registration-form-builder-with-submission-manager'),
            'Atlantic/Faroe' => __('Faroe','custom-registration-form-builder-with-submission-manager'),
            'Atlantic/Madeira' => __('Madeira','custom-registration-form-builder-with-submission-manager'),
            'Atlantic/Reykjavik' => __('Reykjavik','custom-registration-form-builder-with-submission-manager'),
            'Atlantic/South_Georgia' => __('South Georgia','custom-registration-form-builder-with-submission-manager'),
            'Atlantic/Stanley' => __('Stanley','custom-registration-form-builder-with-submission-manager'),
            'Atlantic/St_Helena' => __('St Helena','custom-registration-form-builder-with-submission-manager'),
            'Australia/Adelaide' => __('Adelaide','custom-registration-form-builder-with-submission-manager'),
            'Australia/Brisbane' => __('Brisbane','custom-registration-form-builder-with-submission-manager'),
            'Australia/Broken_Hill' => __('Broken Hill','custom-registration-form-builder-with-submission-manager'),
            'Australia/Currie' => __('Currie','custom-registration-form-builder-with-submission-manager'),
            'Australia/Darwin' => __('Darwin','custom-registration-form-builder-with-submission-manager'),
            'Australia/Eucla' => __('Eucla','custom-registration-form-builder-with-submission-manager'),
            'Australia/Hobart' => __('Hobart','custom-registration-form-builder-with-submission-manager'),
            'Australia/Lindeman' => __('Lindeman','custom-registration-form-builder-with-submission-manager'),
            'Australia/Lord_Howe' => __('Lord Howe','custom-registration-form-builder-with-submission-manager'),
            'Australia/Melbourne' => __('Melbourne','custom-registration-form-builder-with-submission-manager'),
            'Australia/Perth' => __('Perth','custom-registration-form-builder-with-submission-manager'),
            'Australia/Sydney' => __('Sydney','custom-registration-form-builder-with-submission-manager'),
            'Europe/Amsterdam' => __('Amsterdam','custom-registration-form-builder-with-submission-manager'),
            'Europe/Andorra' => __('Andorra','custom-registration-form-builder-with-submission-manager'),
            'Europe/Athens' => __('Athens','custom-registration-form-builder-with-submission-manager'),
            'Europe/Belgrade' => __('Belgrade','custom-registration-form-builder-with-submission-manager'),
            'Europe/Berlin' => __('Berlin','custom-registration-form-builder-with-submission-manager'),
            'Europe/Bratislava' => __('Bratislava','custom-registration-form-builder-with-submission-manager'),
            'Europe/Brussels' => __('Brussels','custom-registration-form-builder-with-submission-manager'),
            'Europe/Bucharest' => __('Bucharest','custom-registration-form-builder-with-submission-manager'),
            'Europe/Budapest' => __('Budapest','custom-registration-form-builder-with-submission-manager'),
            'Europe/Busingen' => __('Busingen','custom-registration-form-builder-with-submission-manager'),
            'Europe/Chisinau' => __('Chisinau','custom-registration-form-builder-with-submission-manager'),
            'Europe/Copenhagen' => __('Copenhagen','custom-registration-form-builder-with-submission-manager'),
            'Europe/Dublin' => __('Dublin','custom-registration-form-builder-with-submission-manager'),
            'Europe/Gibraltar' => __('Gibraltar','custom-registration-form-builder-with-submission-manager'),
            'Europe/Guernsey' => __('Guernsey','custom-registration-form-builder-with-submission-manager'),
            'Europe/Helsinki' => __('Helsinki','custom-registration-form-builder-with-submission-manager'),
            'Europe/Isle_of_Man' => __('Isle of Man','custom-registration-form-builder-with-submission-manager'),
            'Europe/Istanbul' => __('Istanbul','custom-registration-form-builder-with-submission-manager'),
            'Europe/Jersey' => __('Jersey','custom-registration-form-builder-with-submission-manager'),
            'Europe/Kaliningrad' => __('Kaliningrad','custom-registration-form-builder-with-submission-manager'),
            'Europe/Kiev' => __('Kiev','custom-registration-form-builder-with-submission-manager'),
            'Europe/Lisbon' => __('Lisbon','custom-registration-form-builder-with-submission-manager'),
            'Europe/Ljubljana' => __('Ljubljana','custom-registration-form-builder-with-submission-manager'),
            'Europe/London' => __('London','custom-registration-form-builder-with-submission-manager'),
            'Europe/Luxembourg' => __('Luxembourg','custom-registration-form-builder-with-submission-manager'),
            'Europe/Madrid' => __('Madrid','custom-registration-form-builder-with-submission-manager'),
            'Europe/Malta' => __('Malta','custom-registration-form-builder-with-submission-manager'),
            'Europe/Mariehamn' => __('Mariehamn','custom-registration-form-builder-with-submission-manager'),
            'Europe/Minsk' => __('Minsk','custom-registration-form-builder-with-submission-manager'),
            'Europe/Monaco' => __('Monaco','custom-registration-form-builder-with-submission-manager'),
            'Europe/Moscow' => __('Moscow','custom-registration-form-builder-with-submission-manager'),
            'Europe/Oslo' => __('Oslo','custom-registration-form-builder-with-submission-manager'),
            'Europe/Paris' => __('Paris','custom-registration-form-builder-with-submission-manager'),
            'Europe/Podgorica' => __('Podgorica','custom-registration-form-builder-with-submission-manager'),
            'Europe/Prague' => __('Prague','custom-registration-form-builder-with-submission-manager'),
            'Europe/Riga' => __('Riga','custom-registration-form-builder-with-submission-manager'),
            'Europe/Rome' => __('Rome','custom-registration-form-builder-with-submission-manager'),
            'Europe/Samara' => __('Samara','custom-registration-form-builder-with-submission-manager'),
            'Europe/San_Marino' => __('San Marino','custom-registration-form-builder-with-submission-manager'),
            'Europe/Sarajevo' => __('Sarajevo','custom-registration-form-builder-with-submission-manager'),
            'Europe/Simferopol' => __('Simferopol','custom-registration-form-builder-with-submission-manager'),
            'Europe/Skopje' => __('Skopje','custom-registration-form-builder-with-submission-manager'),
            'Europe/Sofia' => __('Sofia','custom-registration-form-builder-with-submission-manager'),
            'Europe/Stockholm' => __('Stockholm','custom-registration-form-builder-with-submission-manager'),
            'Europe/Tallinn' => __('Tallinn','custom-registration-form-builder-with-submission-manager'),
            'Europe/Tirane' => __('Tirane','custom-registration-form-builder-with-submission-manager'),
            'Europe/Uzhgorod' => __('Uzhgorod','custom-registration-form-builder-with-submission-manager'),
            'Europe/Vaduz' => __('Vaduz','custom-registration-form-builder-with-submission-manager'),
            'Europe/Vatican' => __('Vatican','custom-registration-form-builder-with-submission-manager'),
            'Europe/Vienna' => __('Vienna','custom-registration-form-builder-with-submission-manager'),
            'Europe/Vilnius' => __('Vilnius','custom-registration-form-builder-with-submission-manager'),
            'Europe/Volgograd' => __('Volgograd','custom-registration-form-builder-with-submission-manager'),
            'Europe/Warsaw' => __('Warsaw','custom-registration-form-builder-with-submission-manager'),
            'Europe/Zagreb' => __('Zagreb','custom-registration-form-builder-with-submission-manager'),
            'Europe/Zaporozhye' => __('Zaporozhye','custom-registration-form-builder-with-submission-manager'),
            'Europe/Zurich' => __('Zurich','custom-registration-form-builder-with-submission-manager'),
            'Indian/Antananarivo' => __('Antananarivo','custom-registration-form-builder-with-submission-manager'),
            'Indian/Chagos' => __('Chagos','custom-registration-form-builder-with-submission-manager'),
            'Indian/Christmas' => __('Christmas','custom-registration-form-builder-with-submission-manager'),
            'Indian/Cocos' => __('Cocos','custom-registration-form-builder-with-submission-manager'),
            'Indian/Comoro' => __('Comoro','custom-registration-form-builder-with-submission-manager'),
            'Indian/Kerguelen' => __('Kerguelen','custom-registration-form-builder-with-submission-manager'),
            'Indian/Mahe' => __('Mahe','custom-registration-form-builder-with-submission-manager'),
            'Indian/Maldives' => __('Maldives','custom-registration-form-builder-with-submission-manager'),
            'Indian/Mauritius' => __('Mauritius','custom-registration-form-builder-with-submission-manager'),
            'Indian/Mayotte' => __('Mayotte','custom-registration-form-builder-with-submission-manager'),
            'Indian/Reunion' => __('Reunion','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Apia' => __('Apia','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Auckland' => __('Auckland','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Chatham' => __('Chatham','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Chuuk' => __('Chuuk','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Easter' => __('Easter','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Efate' => __('Efate','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Enderbury' => __('Enderbury','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Fakaofo' => __('Fakaofo','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Fiji' => __('Fiji','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Funafuti' => __('Funafuti','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Galapagos' => __('Galapagos','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Gambier' => __('Gambier','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Guadalcanal' => __('Guadalcanal','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Guam' => __('Guam','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Honolulu' => __('Honolulu','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Johnston' => __('Johnston','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Kiritimati' => __('Kiritimati','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Kosrae' => __('Kosrae','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Kwajalein' => __('Kwajalein','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Majuro' => __('Majuro','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Marquesas' => __('Marquesas','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Midway' => __('Midway','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Nauru' => __('Nauru','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Niue' => __('Niue','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Norfolk' => __('Norfolk','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Noumea' => __('Noumea','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Pago_Pago' => __('Pago Pago','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Palau' => __('Palau','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Pitcairn' => __('Pitcairn','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Pohnpei' => __('Pohnpei','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Port_Moresby' => __('Port Moresby','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Rarotonga' => __('Rarotonga','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Saipan' => __('Saipan','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Tahiti' => __('Tahiti','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Tarawa' => __('Tarawa','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Tongatapu' => __('Tongatapu','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Wake' => __('Wake','custom-registration-form-builder-with-submission-manager'),
            'Pacific/Wallis' => __('Wallis','custom-registration-form-builder-with-submission-manager'),
            'UTC' => __('UTC','custom-registration-form-builder-with-submission-manager'),
            'UTC-12' => __('UTC-12','custom-registration-form-builder-with-submission-manager'),
            'UTC-11.5' => __('UTC-11:30','custom-registration-form-builder-with-submission-manager'),
            'UTC-11' => __('UTC-11','custom-registration-form-builder-with-submission-manager'),
            'UTC-10.5' => __('UTC-10:30','custom-registration-form-builder-with-submission-manager'),
            'UTC-10' => __('UTC-10','custom-registration-form-builder-with-submission-manager'),
            'UTC-9.5' => __('UTC-9:30','custom-registration-form-builder-with-submission-manager'),
            'UTC-9' => __('UTC-9','custom-registration-form-builder-with-submission-manager'),
            'UTC-8.5' => __('UTC-8:30','custom-registration-form-builder-with-submission-manager'),
            'UTC-8' => __('UTC-8','custom-registration-form-builder-with-submission-manager'),
            'UTC-7.5' => __('UTC-7:30','custom-registration-form-builder-with-submission-manager'),
            'UTC-7' => __('UTC-7','custom-registration-form-builder-with-submission-manager'),
            'UTC-6.5' => __('UTC-6:30','custom-registration-form-builder-with-submission-manager'),
            'UTC-6' => __('UTC-6','custom-registration-form-builder-with-submission-manager'),
            'UTC-5.5' => __('UTC-5:30','custom-registration-form-builder-with-submission-manager'),
            'UTC-5' => __('UTC-5','custom-registration-form-builder-with-submission-manager'),
            'UTC-4.5' => __('UTC-4:30','custom-registration-form-builder-with-submission-manager'),
            'UTC-4' => __('UTC-4','custom-registration-form-builder-with-submission-manager'),
            'UTC-3.5' => __('UTC-3:30','custom-registration-form-builder-with-submission-manager'),
            'UTC-3' => __('UTC-3','custom-registration-form-builder-with-submission-manager'),
            'UTC-2.5' => __('UTC-2:30','custom-registration-form-builder-with-submission-manager'),
            'UTC-2' => __('UTC-2','custom-registration-form-builder-with-submission-manager'),
            'UTC-1.5' => __('UTC-1:30','custom-registration-form-builder-with-submission-manager'),
            'UTC-1' => __('UTC-1','custom-registration-form-builder-with-submission-manager'),
            'UTC-0.5' => __('UTC-0:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+0' => __('UTC+0','custom-registration-form-builder-with-submission-manager'),
            'UTC+0.5' => __('UTC+0:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+1' => __('UTC+1','custom-registration-form-builder-with-submission-manager'),
            'UTC+1.5' => __('UTC+1:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+2' => __('UTC+2','custom-registration-form-builder-with-submission-manager'),
            'UTC+2.5' => __('UTC+2:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+3' => __('UTC+3','custom-registration-form-builder-with-submission-manager'),
            'UTC+3.5' => __('UTC+3:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+4' => __('UTC+4','custom-registration-form-builder-with-submission-manager'),
            'UTC+4.5' => __('UTC+4:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+5' => __('UTC+5','custom-registration-form-builder-with-submission-manager'),
            'UTC+5.5' => __('UTC+5:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+5.75' => __('UTC+5:45','custom-registration-form-builder-with-submission-manager'),
            'UTC+6' => __('UTC+6','custom-registration-form-builder-with-submission-manager'),
            'UTC+6.5' => __('UTC+6:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+7' => __('UTC+7','custom-registration-form-builder-with-submission-manager'),
            'UTC+7.5' => __('UTC+7:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+8' => __('UTC+8','custom-registration-form-builder-with-submission-manager'),
            'UTC+8.5' => __('UTC+8:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+8.75' => __('UTC+8:45','custom-registration-form-builder-with-submission-manager'),
            'UTC+9' => __('UTC+9','custom-registration-form-builder-with-submission-manager'),
            'UTC+9.5' => __('UTC+9:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+10' => __('UTC+10','custom-registration-form-builder-with-submission-manager'),
            'UTC+10.5' => __('UTC+10:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+11' => __('UTC+11','custom-registration-form-builder-with-submission-manager'),
            'UTC+11.5' => __('UTC+11:30','custom-registration-form-builder-with-submission-manager'),
            'UTC+12' => __('UTC+12','custom-registration-form-builder-with-submission-manager'),
            'UTC+12.75' => __('UTC+12:45','custom-registration-form-builder-with-submission-manager'),
            'UTC+13' => __('UTC+13','custom-registration-form-builder-with-submission-manager'),
            'UTC+13.75' => __('UTC+13:45','custom-registration-form-builder-with-submission-manager'),
            'UTC+14' => __('UTC+14','custom-registration-form-builder-with-submission-manager')
        );
        
        return $timezones;
    }

    public static function get_currency_symbol($currency = null) {
        $curr_arr = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AUD' => '$',
            'BRL' => 'R$',
            'CAD' => '$',
            'CZK' => 'Kč',
            'DKK' => 'kr',
            'HKD' => '$',
            'HRK' => 'kn',
            'HUF' => 'Ft',
            'IDR' => 'Rp',
            'ILS' => '₪',
            'JPY' => '¥',
            'MYR' => 'RM',
            'MXN' => '$',
            'NZD' => '$',
            'NOK' => 'kr',
            'PHP' => '₱',
            'PLN' => 'zł',
            'SGD' => '$',
            'SEK' => 'kr',
            'CHF' => 'CHF',
            'TWD' => 'NT$',
            'THB' => '฿',
            'INR' => '₹',
            'TRY' => 'TRY',
            'RIAL' => 'RIAL',
            'RON' => 'lei',
            'RUB' => 'руб',
            'NGN' => '&#x20a6;',
            'ZAR' => 'R',
            'ZMW' => 'ZK',
            'GHS' => 'GH&#x20B5;',
            'KES' => 'KSh',
            'UGX' => 'UGX',
            'TZS' => 'TSh',
            'OMR' => 'ريال'
        );
        return isset($curr_arr[(string)$currency]) ? html_entity_decode($curr_arr[$currency]) : $currency;
    }

    public static function check_src_type($string) {
        if (strpos($string, 'youtube') > 0) {
            return 'youtube';
        } elseif (strpos($string, 'vimeo') > 0) {
            return 'vimeo';
        } else {
            return 'unknown';
        }
    }

    public static function extract_vimeo_embed_src($string) {
        return (int) substr(parse_url($string, PHP_URL_PATH), 1);
    }

    public static function extract_youtube_embed_src($string) {
        return preg_replace(
                "/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i", "$2", $string
        );
    }

    public static function get_country_code($country){
        $code='';
        preg_match("/\[[A-Z]{2}\]/", $country, $matches);
        if (!empty($matches)) {
            preg_match("/[A-Z]{2}/", $matches[0], $matches);
            if (!empty($matches)) {
                $code = strtolower($matches[0]);
            }
        }  
        return $code;
    }

    public static function get_feed_widget_html($field_id) {
        $field= new RM_Fields();
        $field->load_from_db($field_id);
        
        $class=  $field->field_options->field_css_class;
        $limit=  (int)$field->field_options->max_items>0 ? $field->field_options->max_items : 5;
        $html = "<div class='rmform-sumission-widget-row $class'>";
        $initial='';
        $form_id= $field->get_form_id();
        $form= new RM_Forms();
        $form->load_from_db($form_id);

        if($form->get_form_type()==1){
            $user_repo= new RM_User_Repository();
            $users= $user_repo->get_users_for_front(array("form_id"=>$form_id,'limit'=>$limit));

            if(is_array($users) && count($users)>0){
            foreach($users as $user){
                if(empty($user->ID))
                    continue;
                $initial='';
                $value= $field->field_value;
                if($value=="user_login"){
                    $initial= $user->user_login;
                } else if($value=="first_name"){
                    $initial= get_user_meta($user->ID, "first_name", true);
                    $initial= empty($initial) ? $user->display_name : $initial;
                } else if($value=="last_name"){
                     $initial= get_user_meta($user->ID, "last_name", true);
                     $initial= empty($initial) ? $user->display_name : $initial;
                } else if($value=="custom"){
                    $initial= $field->field_options->custom_value;
                } else if($value=="display_name"){
                    $initial= $user->display_name;
                } else if($value=='in_last_name'){
                    $first_name= get_user_meta($user->ID, "first_name", true);
                    $last_name= get_user_meta($user->ID, "last_name", true);
                    if(empty($first_name) && empty($last_name)){
                        $initial= $user->display_name;
                    } else{
                        $first_initial= !empty($first_name) ? strtoupper($first_name[0]) : '';
                        $initial= $first_initial.' '.ucwords($last_name);
                    }
                }
                else if($value=="both_names"){
                    $first_name= get_user_meta($user->ID, "first_name", true);
                    $last_name= get_user_meta($user->ID, "last_name", true);
                    if(empty($first_name) && empty($last_name)){
                        $initial= $user->display_name;
                    } else
                    $initial= $first_name.' '.$last_name;
                }
                $html .= "<div class='rm-rgfeed'>";
                 if($field->field_options->show_gravatar){
                    $html .= "<span class='rm-avatar'>".get_avatar($user->user_email)."</span>";
                }
                $html .="<div class='rm-rgfeed-user-info'> <span class='rm-rgfeed-user'>$initial </span>";
                if(!$field->field_options->hide_date){
                    if(empty($user->user_registered)){
                        $submission= RM_DBManager::get_submissions_for_user($user->user_email,1);
                        if(!empty($submission)){
                           $html .= RM_UI_Strings::get("LABEL_UNREGISTERED_SUB")." <b>".self::format_on_time($submission[0]->submitted_on)."</b>";
                        }
                        
                    } else {
                        $html .= RM_UI_Strings::get("LABEL_REGISTERED_ON")." <b>".self::format_on_time($user->user_registered)."</b>";
                    }
                }
                else{
                    if(empty($user->user_registered)){
                        $submission= RM_DBManager::get_submissions_for_user($user->user_email,1);
                        if(!empty($submission)){
                           $html .= RM_UI_Strings::get("LABEL_UNREGISTERED_SUB");
                        }
                    } else{
                        $html .= RM_UI_Strings::get("LABEL_REGISTERED_ON");
                    }
                }
      
                
                if(!$field->field_options->hide_country){ 
                    $submissions= RM_DBManager::get_latest_submission_for_user($user->user_email,array($form_id));
                    if(!empty($submissions) && is_array($submissions))
                    {
                        $data= maybe_unserialize($submissions[0]->data);
                        $country='';
                        $country_field= RM_DBManager::get_field_by_type($form_id,'Country');
                        if(!empty($country_field) && isset($data[$country_field->field_id])){
                            $country= $data[$country_field->field_id]->value;
                            preg_match("/\[[A-Z]{2}\]/", $country,$matches);
                            if(!empty($matches)){
                                preg_match("/[A-Z]{2}/",$matches[0],$matches);
                                if(!empty($matches)){
                                    $flag= strtolower($matches[0]);
                                    $country_name= str_replace("["."$matches[0]"."]", '', $country);
                                    $country= '<b>'.$country_name.'</b> <img class="rm_country_flag" src="'.RM_IMG_URL.'flag/16/'.$flag.'.png" />';
                                }
                                
                            }     
                        }
                        if(!empty($country))
                             $html .= " from $country ";
                    }
                }
                $html .=" </div></div>";
            }
            } 
        } else {
            $submissions = RM_DBManager::get_submissions_for_form($form_id,$limit,0,'*','submitted_on',true);
            
            $value= $field->field_value;
            if($value=='custom'){
                $initial= $field->field_options->custom_value.' ';
            }
            else
            {
                $initial= ' User ';
            }
            if(!empty($submissions)) {
                foreach($submissions as $submission) {
                    $data= maybe_unserialize($submission->data);
                    $html .= "<div class='rm-rgfeed'> ";
                
                    if($field->field_options->show_gravatar){
                        $html .= "<span class='rm-avatar'>".get_avatar($submission->user_email)."</span>";
                    }
                        $html .="<div class='rm-rgfeed-user-info'><span class='rm-rgfeed-user'>$initial</span>";
                    if(!$field->field_options->hide_date){
                        $html .= RM_UI_Strings::get("LABEL_SUBMITTED_ON")." <b>". self::format_on_time($submission->submitted_on)."</b>";
                    }
                    if(!$field->field_options->hide_country){
                        $data= maybe_unserialize($submission->data);
                        $country='';
                        $country_field= RM_DBManager::get_field_by_type($form_id,'Country');
                        if(!empty($country_field) && isset($data[$country_field->field_id])){
                            $country= $data[$country_field->field_id]->value;   
                            preg_match("/\[[A-Z]{2}\]/",$country,$matches);
                                if(!empty($matches)){
                                    preg_match("/[A-Z]{2}/",$matches[0],$matches);
                                    if(!empty($matches)){
                                        $flag= strtolower($matches[0]);
                                        $country_name= str_replace("["."$matches[0]"."]", '', $country);
                                        $country = '<b>'.$country_name.'</b> <img class="rm_country_flag" src="'.RM_IMG_URL.'flag/16/'.$flag.'.png" />';
                                    }
                                    
                            }  
                        }
                        if(!empty($country))
                            $html .= " from $country";
                    }
                    $html .="</div> </div>";
                
                }
            }
        }
      $html .= "</div>";
      return $html;
    }

    public static function get_formdata_widget_html($field_id) {
        $field = new RM_Fields();
        $field->load_from_db($field_id);

        $class = $field->field_options->field_css_class;
        $html = "<div class='rmrow'><div class='fdata-row'>";
        $form_name = '';
        $form = new RM_Forms();
        $form->load_from_db($field->get_form_id());
        $stats = new RM_Analytics_Service();
        $stats_data = $stats->calculate_form_stats($field->get_form_id());
        $options = array("nu_form_views" => array("nu_views_text_before", "nu_views_text_after"),
            "nu_submissions" => array("nu_sub_text_before", "nu_sub_text_after"),
            "sub_limits" => array("sub_limit_text_before", "sub_limit_text_after"),
            "sub_date_limits" => array("sub_date_limit_text_before", "sub_date_limit_text_after"),
            "last_sub_rec" => array("ls_text_before", "ls_text_after"));

        foreach ($options as $key => $values) {
            $value = '';
            if (!empty($field->field_options->{$key}) && $field->field_options->{$key}) {
                if ($key == 'nu_form_views') {
                    $value = $stats_data->total_entries;
                } else if ($key == 'nu_submissions') {
                    $value = $stats_data->successful_submission;
                } else if ($key == "sub_limits") {
                    $fo = $form->form_options;
                    $value = $fo->form_submissions_limit;
                } else if ($key == "sub_date_limits") {
                    $limit_type = empty($field->field_options->sub_limit_ind) ? 'date' : $field->field_options->sub_limit_ind;
                    $fo = $form->form_options;
                    if ($form->get_form_should_auto_expire()) {
                        if (!empty($fo->form_expiry_date)) {
                            if ($limit_type == "days") {
                                $diff = strtotime($fo->form_expiry_date) - time();
                                if ($diff > 0) {
                                    $value = floor($diff / (60 * 60 * 24)) . ' Days ';
                                }
                            } else {
                                $value = $fo->form_expiry_date;
                            }
                        }
                    }
                } else if ($key == "last_sub_rec") {
                    $submission = RM_DBManager::get_last_submission();
                    if (!empty($submission)) {
                        $visited_on = strtotime($submission->submitted_on);
                        if (!empty($visited_on)) {
                            $visited_on = self::convert_to_mysql_timestamp(strtotime($submission->submitted_on));
                            $visited_on = self::localize_time($visited_on, 'd M Y, h:ia');
                            $value = $visited_on;
                        }
                    }
                }
                $html .= $field->field_options->{$values[0]} . " <span>$value</span> " . $field->field_options->{$values[1]} . '<br>';
            }
        }
        if ($field->field_options->show_form_name) {
            $html .= '<div class="rm-form-name"><h3>' . $form->get_form_name() . '</h3></div>';
        }

        if ($field->field_options->form_desc) {
            $html .= '<div class="rm-form-name">' . $form->form_options->form_description . '</div>';
        }

        $html .= "</div></div>";
        return $html;
    }

    public static function get_form_expiry_message($form_id){
        $service= new RM_Services();
        $form= new RM_Forms();
        $form->load_from_db($form_id);
        $expiry_details = $service->get_form_expiry_stats($form);
        $exp_str='';
        if (!empty($expiry_details) && $expiry_details->state !== 'perpetual') {
            if ($expiry_details->state === 'expired') {
                $exp_str .= '<div class="rm-formcard-expired">' .__("Expired", 'custom-registration-form-builder-with-submission-manager'). '</div>';
            } else {
                switch ($expiry_details->criteria) {
                    case 'both':
                        $message = sprintf(RM_UI_Strings::get('EXPIRY_DETAIL_BOTH'), ($expiry_details->sub_limit - $expiry_details->remaining_subs), $expiry_details->sub_limit, $expiry_details->remaining_days);
                        $exp_str .= '<div class="rm-formcard-expired"><span class="rm_sandclock"></span>' . $message . '</div>';
                        break;
                    case 'subs':
                        $total = $expiry_details->sub_limit;
                        $rem = $expiry_details->remaining_subs;
                        $wtot = 100;
                        $rem = ($rem * 100) / $total;
                        $done = 100 - $rem;
                        $message = sprintf(RM_UI_Strings::get('EXPIRY_DETAIL_SUBS'), ($expiry_details->sub_limit - $expiry_details->remaining_subs), $expiry_details->sub_limit);
                        $exp_str .= '<div class="rm-formcard-expired"><span class="rm_sandclock"></span>' . $message . '</div>';
                        break;

                    case 'date':
                        $message = sprintf(RM_UI_Strings::get('EXPIRY_DETAIL_DATE'), $expiry_details->remaining_days);
                        $exp_str .= '<div class="rm-formcard-expired"><span class="rm_sandclock"></span>' . $message . '</div>';
                        break;
                }
            } 
        }
        return $exp_str;
   }
}