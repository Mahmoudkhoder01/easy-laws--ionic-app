<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Currencies' ) ) :

class BW_WC_Currencies {

	public function __construct() {
		$currencies = $this->currencies();
		foreach( $currencies as $data ) {
			$this->currency_symbols[ $data['code'] ]  = $data['symbol'];
			$this->currency_names[ $data['code'] ]    = $data['name'];
			//$this->currency_names_and_symbols[ $data['code'] ] = $data['name'] . ' (' . $data['symbol'] . ')';
		}
		// Main hooks
		add_filter( 'woocommerce_currencies',       array( $this, 'add_all_currencies'), 100 );
		add_filter( 'woocommerce_currency_symbol',  array( $this, 'add_currency_symbol'), 100, 2 );
		// Settings
		add_filter( 'woocommerce_general_settings', array( $this, 'add_edit_currency_symbol_field' ), 100 );
	}

	function add_all_currencies( $currencies ) {
		foreach ( $this->currency_names as $currency_code => $currency_name )
			$currencies[ $currency_code ] = $currency_name;
		asort( $currencies );
		return $currencies;
	}

	function add_currency_symbol( $currency_symbol, $currency ) {
		$default = ( isset( $this->currency_symbols[ $currency ] ) ) ? $this->currency_symbols[ $currency ] : $currency_symbol;
		return apply_filters( 'shop_get_currency_symbol', $default, get_option( 'shop_currency_' . $currency, $currency_symbol ) );
	}

	function add_edit_currency_symbol_field( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			if ( isset( $section['id'] ) && 'woocommerce_currency_pos' == $section['id'] ) {
				$updated_settings[] = array(
					'name'		=> __( 'Currency Symbol', BW_TD ), //TODO name or title?????
					'desc_tip'	=> __( 'This sets the currency symbol.', BW_TD ),
					'id'		=> 'shop_currency_' . get_woocommerce_currency(),
					'type'		=> 'text',
					'default'	=> get_woocommerce_currency_symbol(),
					'desc'		=> '',
					'css'		=> 'width: 50px;',
					'custom_attributes'
								=> '',
				);
			}
			$updated_settings[] = $section;
		}
		return $updated_settings;
	}

	public function currencies(){
		return array(
			array( 'code' => 'AFN', 'name' => __( 'Afghan afghani' ), 'symbol' => 'AFN', ),
			array( 'code' => 'ALL', 'name' => __( 'Albanian lek' ), 'symbol' => 'ALL', ),
			array( 'code' => 'DZD', 'name' => __( 'Algerian dinar' ), 'symbol' => 'DZD', ),
			array( 'code' => 'AOA', 'name' => __( 'Angolan kwanza' ), 'symbol' => 'AOA', ),
			array( 'code' => 'ARS', 'name' => __( 'Argentine peso' ), 'symbol' => 'ARS', ),
			array( 'code' => 'AMD', 'name' => __( 'Armenian dram' ), 'symbol' => 'AMD', ),
			array( 'code' => 'AWG', 'name' => __( 'Aruban florin' ), 'symbol' => 'AWG', ),
			array( 'code' => 'AUD', 'name' => __( 'Australian dollar' ), 'symbol' => '&#36;', ),
			array( 'code' => 'AZN', 'name' => __( 'Azerbaijani manat' ), 'symbol' => 'AZN', ),
			array( 'code' => 'BSD', 'name' => __( 'Bahamian dollar' ), 'symbol' => 'BSD', ),
			array( 'code' => 'BHD', 'name' => __( 'Bahraini dinar' ), 'symbol' => 'BHD', ),
			array( 'code' => 'BDT', 'name' => __( 'Bangladeshi taka' ), 'symbol' => 'BDT', ),
			array( 'code' => 'BBD', 'name' => __( 'Barbadian dollar' ), 'symbol' => 'BBD', ),
			array( 'code' => 'BYR', 'name' => __( 'Belarusian ruble' ), 'symbol' => 'BYR', ),
			array( 'code' => 'BZD', 'name' => __( 'Belize dollar' ), 'symbol' => 'BZD', ),
			array( 'code' => 'BTN', 'name' => __( 'Bhutanese ngultrum' ), 'symbol' => 'BTN', ),
			array( 'code' => 'BOB', 'name' => __( 'Bolivian boliviano' ), 'symbol' => 'BOB', ),
			array( 'code' => 'BAM', 'name' => __( 'Bosnia and Herzegovina konvertibilna marka' ), 'symbol' => 'BAM', ),
			array( 'code' => 'BWP', 'name' => __( 'Botswana pula' ), 'symbol' => 'BWP', ),
			array( 'code' => 'BRL', 'name' => __( 'Brazilian real' ), 'symbol' => '&#82;&#36;', ),
			array( 'code' => 'GBP', 'name' => __( 'British pound' ), 'symbol' => '&pound;', ),
			array( 'code' => 'BND', 'name' => __( 'Brunei dollar' ), 'symbol' => 'BND', ),
			array( 'code' => 'BGN', 'name' => __( 'Bulgarian lev' ), 'symbol' => 'BGN', ),
			array( 'code' => 'BIF', 'name' => __( 'Burundi franc' ), 'symbol' => 'BIF', ),
			array( 'code' => 'KYD', 'name' => __( 'Cayman Islands dollar' ), 'symbol' => 'KYD', ),
			array( 'code' => 'KHR', 'name' => __( 'Cambodian riel' ), 'symbol' => 'KHR', ),
			array( 'code' => 'CAD', 'name' => __( 'Canadian dollar' ), 'symbol' => 'CAD', ),
			array( 'code' => 'CVE', 'name' => __( 'Cape Verdean escudo' ), 'symbol' => 'CVE', ),
			array( 'code' => 'XAF', 'name' => __( 'Central African CFA franc' ), 'symbol' => 'XAF', ),
			array( 'code' => 'GQE', 'name' => __( 'Central African CFA franc' ), 'symbol' => 'GQE', ),
			array( 'code' => 'XPF', 'name' => __( 'CFP franc' ), 'symbol' => 'XPF', ),
			array( 'code' => 'CLP', 'name' => __( 'Chilean peso' ), 'symbol' => 'CLP', ),
			array( 'code' => 'CNY', 'name' => __( 'Chinese renminbi' ), 'symbol' => '&yen;', ),
			array( 'code' => 'COP', 'name' => __( 'Colombian peso' ), 'symbol' => 'COP', ),
			array( 'code' => 'KMF', 'name' => __( 'Comorian franc' ), 'symbol' => 'KMF', ),
			array( 'code' => 'CDF', 'name' => __( 'Congolese franc' ), 'symbol' => 'CDF', ),
			array( 'code' => 'CRC', 'name' => __( 'Costa Rican colon' ), 'symbol' => 'CRC', ),
			array( 'code' => 'HRK', 'name' => __( 'Croatian kuna' ), 'symbol' => 'HRK', ),
			array( 'code' => 'CUC', 'name' => __( 'Cuban peso' ), 'symbol' => 'CUC', ),
			array( 'code' => 'CZK', 'name' => __( 'Czech koruna' ), 'symbol' => '&#75;&#269;', ),
			array( 'code' => 'DKK', 'name' => __( 'Danish krone' ), 'symbol' => '&#107;&#114;', ),
			array( 'code' => 'DJF', 'name' => __( 'Djiboutian franc' ), 'symbol' => 'DJF', ),
			array( 'code' => 'DOP', 'name' => __( 'Dominican peso' ), 'symbol' => 'DOP', ),
			array( 'code' => 'XCD', 'name' => __( 'East Caribbean dollar' ), 'symbol' => 'XCD', ),
			array( 'code' => 'EGP', 'name' => __( 'Egyptian pound' ), 'symbol' => 'EGP', ),
			array( 'code' => 'ERN', 'name' => __( 'Eritrean nakfa' ), 'symbol' => 'ERN', ),
			array( 'code' => 'EEK', 'name' => __( 'Estonian kroon' ), 'symbol' => 'EEK', ),
			array( 'code' => 'ETB', 'name' => __( 'Ethiopian birr' ), 'symbol' => 'ETB', ),
			array( 'code' => 'EUR', 'name' => __( 'European euro' ), 'symbol' => '&euro;', ),
			array( 'code' => 'FKP', 'name' => __( 'Falkland Islands pound' ), 'symbol' => 'FKP', ),
			array( 'code' => 'FJD', 'name' => __( 'Fijian dollar' ), 'symbol' => 'FJD', ),
			array( 'code' => 'GMD', 'name' => __( 'Gambian dalasi' ), 'symbol' => 'GMD', ),
			array( 'code' => 'GEL', 'name' => __( 'Georgian lari' ), 'symbol' => 'GEL', ),
			array( 'code' => 'GHS', 'name' => __( 'Ghanaian cedi' ), 'symbol' => 'GHS', ),
			array( 'code' => 'GIP', 'name' => __( 'Gibraltar pound' ), 'symbol' => 'GIP', ),
			array( 'code' => 'GTQ', 'name' => __( 'Guatemalan quetzal' ), 'symbol' => 'GTQ', ),
			array( 'code' => 'GNF', 'name' => __( 'Guinean franc' ), 'symbol' => 'GNF', ),
			array( 'code' => 'GYD', 'name' => __( 'Guyanese dollar' ), 'symbol' => 'GYD', ),
			array( 'code' => 'HTG', 'name' => __( 'Haitian gourde' ), 'symbol' => 'HTG', ),
			array( 'code' => 'HNL', 'name' => __( 'Honduran lempira' ), 'symbol' => 'HNL', ),
			array( 'code' => 'HKD', 'name' => __( 'Hong Kong dollar' ), 'symbol' => '&#36;', ),
			array( 'code' => 'HUF', 'name' => __( 'Hungarian forint' ), 'symbol' => '&#70;&#116;', ),
			array( 'code' => 'ISK', 'name' => __( 'Icelandic krona' ), 'symbol' => 'ISK', ),
			array( 'code' => 'INR', 'name' => __( 'Indian rupee' ), 'symbol' => '&#8377;', ),
			array( 'code' => 'IDR', 'name' => __( 'Indonesian rupiah' ), 'symbol' => 'Rp', ),
			array( 'code' => 'IRR', 'name' => __( 'Iranian rial' ), 'symbol' => 'IRR', ),
			array( 'code' => 'IQD', 'name' => __( 'Iraqi dinar' ), 'symbol' => 'IQD', ),
			array( 'code' => 'ILS', 'name' => __( 'Israeli new sheqel' ), 'symbol' => '&#8362;', ),
			array( 'code' => 'YER', 'name' => __( 'Yemeni rial' ), 'symbol' => 'YER', ),
			array( 'code' => 'JMD', 'name' => __( 'Jamaican dollar' ), 'symbol' => 'JMD', ),
			array( 'code' => 'JPY', 'name' => __( 'Japanese yen' ), 'symbol' => '&yen;', ),
			array( 'code' => 'JOD', 'name' => __( 'Jordanian dinar' ), 'symbol' => 'JOD', ),
			array( 'code' => 'KZT', 'name' => __( 'Kazakhstani tenge' ), 'symbol' => 'KZT', ),
			array( 'code' => 'KES', 'name' => __( 'Kenyan shilling' ), 'symbol' => 'KES', ),
			array( 'code' => 'KGS', 'name' => __( 'Kyrgyzstani som' ), 'symbol' => 'KGS', ),
			array( 'code' => 'KWD', 'name' => __( 'Kuwaiti dinar' ), 'symbol' => 'KWD', ),
			array( 'code' => 'LAK', 'name' => __( 'Lao kip' ), 'symbol' => 'LAK', ),
			//array( 'code' => 'KIP', 'name' => __( 'Lao kip' ), 'symbol' => 'KIP', ),
			array( 'code' => 'LVL', 'name' => __( 'Latvian lats' ), 'symbol' => 'LVL', ),
			array( 'code' => 'LBP', 'name' => __( 'Lebanese lira' ), 'symbol' => 'LBP', ),
			array( 'code' => 'LSL', 'name' => __( 'Lesotho loti' ), 'symbol' => 'LSL', ),
			array( 'code' => 'LRD', 'name' => __( 'Liberian dollar' ), 'symbol' => 'LRD', ),
			array( 'code' => 'LYD', 'name' => __( 'Libyan dinar' ), 'symbol' => 'LYD', ),
			array( 'code' => 'LTL', 'name' => __( 'Lithuanian litas' ), 'symbol' => 'LTL', ),
			array( 'code' => 'MOP', 'name' => __( 'Macanese pataca' ), 'symbol' => 'MOP', ),
			array( 'code' => 'MKD', 'name' => __( 'Macedonian denar' ), 'symbol' => 'MKD', ),
			array( 'code' => 'MGA', 'name' => __( 'Malagasy ariary' ), 'symbol' => 'MGA', ),
			array( 'code' => 'MYR', 'name' => __( 'Malaysian ringgit' ), 'symbol' => '&#82;&#77;', ),
			array( 'code' => 'MWK', 'name' => __( 'Malawian kwacha' ), 'symbol' => 'MWK', ),
			array( 'code' => 'MVR', 'name' => __( 'Maldivian rufiyaa' ), 'symbol' => 'MVR', ),
			array( 'code' => 'MRO', 'name' => __( 'Mauritanian ouguiya' ), 'symbol' => 'MRO', ),
			array( 'code' => 'MUR', 'name' => __( 'Mauritian rupee' ), 'symbol' => 'MUR', ),
			array( 'code' => 'MXN', 'name' => __( 'Mexican peso' ), 'symbol' => '&#36;', ),
			array( 'code' => 'MMK', 'name' => __( 'Myanma kyat' ), 'symbol' => 'MMK', ),
			array( 'code' => 'MDL', 'name' => __( 'Moldovan leu' ), 'symbol' => 'MDL', ),
			array( 'code' => 'MNT', 'name' => __( 'Mongolian tugrik' ), 'symbol' => 'MNT', ),
			array( 'code' => 'MAD', 'name' => __( 'Moroccan dirham' ), 'symbol' => 'MAD', ),
			array( 'code' => 'MZM', 'name' => __( 'Mozambican metical' ), 'symbol' => 'MZM', ),
			array( 'code' => 'NAD', 'name' => __( 'Namibian dollar' ), 'symbol' => 'NAD', ),
			array( 'code' => 'NPR', 'name' => __( 'Nepalese rupee' ), 'symbol' => 'NPR', ),
			array( 'code' => 'ANG', 'name' => __( 'Netherlands Antillean gulden' ), 'symbol' => 'ANG', ),
			array( 'code' => 'TWD', 'name' => __( 'New Taiwan dollar' ), 'symbol' => '&#78;&#84;&#36;', ),
			array( 'code' => 'NZD', 'name' => __( 'New Zealand dollar' ), 'symbol' => '&#36;', ),
			array( 'code' => 'NIO', 'name' => __( 'Nicaraguan cordoba' ), 'symbol' => 'NIO', ),
			array( 'code' => 'NGN', 'name' => __( 'Nigerian naira' ), 'symbol' => 'NGN', ),
			array( 'code' => 'KPW', 'name' => __( 'North Korean won' ), 'symbol' => 'KPW', ),
			array( 'code' => 'NOK', 'name' => __( 'Norwegian krone' ), 'symbol' => '&#107;&#114;', ),
			array( 'code' => 'OMR', 'name' => __( 'Omani rial' ), 'symbol' => 'OMR', ),
			array( 'code' => 'TOP', 'name' => __( 'Paanga' ), 'symbol' => 'TOP', ),
			array( 'code' => 'PKR', 'name' => __( 'Pakistani rupee' ), 'symbol' => 'PKR', ),
			array( 'code' => 'PAB', 'name' => __( 'Panamanian balboa' ), 'symbol' => 'PAB', ),
			array( 'code' => 'PGK', 'name' => __( 'Papua New Guinean kina' ), 'symbol' => 'PGK', ),
			array( 'code' => 'PYG', 'name' => __( 'Paraguayan guarani' ), 'symbol' => 'PYG', ),
			array( 'code' => 'PEN', 'name' => __( 'Peruvian nuevo sol' ), 'symbol' => 'PEN', ),
			array( 'code' => 'PHP', 'name' => __( 'Philippine peso' ), 'symbol' => '&#8369;', ),
			array( 'code' => 'PLN', 'name' => __( 'Polish zloty' ), 'symbol' => '&#122;&#322;', ),
			array( 'code' => 'QAR', 'name' => __( 'Qatari riyal' ), 'symbol' => 'QAR', ),
			array( 'code' => 'RON', 'name' => __( 'Romanian leu' ), 'symbol' => 'lei', ),
			array( 'code' => 'RUB', 'name' => __( 'Russian ruble' ), 'symbol' => 'RUB', ),
			array( 'code' => 'RWF', 'name' => __( 'Rwandan franc' ), 'symbol' => 'RWF', ),
			array( 'code' => 'SHP', 'name' => __( 'Saint Helena pound' ), 'symbol' => 'SHP', ),
			array( 'code' => 'WST', 'name' => __( 'Samoan tala' ), 'symbol' => 'WST', ),
			array( 'code' => 'STD', 'name' => __( 'Sao Tome and Principe dobra' ), 'symbol' => 'STD', ),
			array( 'code' => 'SAR', 'name' => __( 'Saudi riyal' ), 'symbol' => 'SAR', ),
			array( 'code' => 'SCR', 'name' => __( 'Seychellois rupee' ), 'symbol' => 'SCR', ),
			array( 'code' => 'RSD', 'name' => __( 'Serbian dinar' ), 'symbol' => 'RSD', ),
			array( 'code' => 'SLL', 'name' => __( 'Sierra Leonean leone' ), 'symbol' => 'SLL', ),
			array( 'code' => 'SGD', 'name' => __( 'Singapore dollar' ), 'symbol' => '&#36;', ),
			array( 'code' => 'SYP', 'name' => __( 'Syrian pound' ), 'symbol' => 'SYP', ),
			array( 'code' => 'SKK', 'name' => __( 'Slovak koruna' ), 'symbol' => 'SKK', ),
			array( 'code' => 'SBD', 'name' => __( 'Solomon Islands dollar' ), 'symbol' => 'SBD', ),
			array( 'code' => 'SOS', 'name' => __( 'Somali shilling' ), 'symbol' => 'SOS', ),
			array( 'code' => 'ZAR', 'name' => __( 'South African rand' ), 'symbol' => '&#82;', ),
			array( 'code' => 'KRW', 'name' => __( 'South Korean won' ), 'symbol' => '&#8361;', ),
			array( 'code' => 'XDR', 'name' => __( 'Special Drawing Rights' ), 'symbol' => 'XDR', ),
			array( 'code' => 'LKR', 'name' => __( 'Sri Lankan rupee' ), 'symbol' => 'LKR', ),
			array( 'code' => 'SDG', 'name' => __( 'Sudanese pound' ), 'symbol' => 'SDG', ),
			array( 'code' => 'SRD', 'name' => __( 'Surinamese dollar' ), 'symbol' => 'SRD', ),
			array( 'code' => 'SZL', 'name' => __( 'Swazi lilangeni' ), 'symbol' => 'SZL', ),
			array( 'code' => 'SEK', 'name' => __( 'Swedish krona' ), 'symbol' => '&#107;&#114;', ),
			array( 'code' => 'CHF', 'name' => __( 'Swiss franc' ), 'symbol' => '&#67;&#72;&#70;', ),
			array( 'code' => 'TJS', 'name' => __( 'Tajikistani somoni' ), 'symbol' => 'TJS', ),
			array( 'code' => 'TZS', 'name' => __( 'Tanzanian shilling' ), 'symbol' => 'TZS', ),
			array( 'code' => 'THB', 'name' => __( 'Thai baht' ), 'symbol' => '&#3647;', ),
			array( 'code' => 'TTD', 'name' => __( 'Trinidad and Tobago dollar' ), 'symbol' => 'TTD', ),
			array( 'code' => 'TND', 'name' => __( 'Tunisian dinar' ), 'symbol' => 'TND', ),
			array( 'code' => 'TRY', 'name' => __( 'Turkish new lira' ), 'symbol' => '&#84;&#76;', ),
			array( 'code' => 'TMM', 'name' => __( 'Turkmen manat' ), 'symbol' => 'TMM', ),
			array( 'code' => 'AED', 'name' => __( 'UAE dirham' ), 'symbol' => 'AED', ),
			array( 'code' => 'UGX', 'name' => __( 'Ugandan shilling' ), 'symbol' => 'UGX', ),
			array( 'code' => 'UAH', 'name' => __( 'Ukrainian hryvnia' ), 'symbol' => 'UAH', ),
			array( 'code' => 'USD', 'name' => __( 'United States dollar' ), 'symbol' => '&#36;', ),
			array( 'code' => 'UYU', 'name' => __( 'Uruguayan peso' ), 'symbol' => 'UYU', ),
			array( 'code' => 'UZS', 'name' => __( 'Uzbekistani som' ), 'symbol' => 'UZS', ),
			array( 'code' => 'VUV', 'name' => __( 'Vanuatu vatu' ), 'symbol' => 'VUV', ),
			array( 'code' => 'VEF', 'name' => __( 'Venezuelan bolivar' ), 'symbol' => 'VEF', ),
			array( 'code' => 'VND', 'name' => __( 'Vietnamese dong' ), 'symbol' => 'VND', ),
			array( 'code' => 'XOF', 'name' => __( 'West African CFA franc' ), 'symbol' => 'XOF', ),
			array( 'code' => 'ZMK', 'name' => __( 'Zambian kwacha' ), 'symbol' => 'ZMK', ),
			array( 'code' => 'ZWD', 'name' => __( 'Zimbabwean dollar' ), 'symbol' => 'ZWD', ),
			array( 'code' => 'RMB', 'name' => __( 'Chinese Yuan' ), 'symbol' => '&yen;', ),

		);
	}
}

endif;

return new BW_WC_Currencies();
