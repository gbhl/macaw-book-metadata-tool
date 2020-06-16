<?php
	$segment_genres = array(	
		array("1", "Article"),
		array("2", "Book"),
		array("4", "Chapter"),
		array("8", "Conference"),
		array("14", "Correspondence"),
		array("6", "Issue"),
		array("17", "List"),
		array("16", "Manuscript"),
		array("18", "Notes"),
		array("9", "Preprint"),
		array("7", "Proceeding"),
		array("15", "Review"),
		array("13", "Thesis"),
		array("11", "Treatment"),
		array("10", "Unknown"));

	$segment_identifiers = array(
		array("6", "Abbreviation"),
		array("31", "BioLib.cz"),
		array("16", "BioStor"),
		array("7", "BPH"),
		array("20", "Catalogue of Life"),
		array("10", "CODEN"),
		array("9", "DDC"),
		array("5", "DLC"),
		array("18", "EOL"),
		array("30", "EUNIS"),
		array("28", "GBIF Taxonomic Backbone"),
		array("19", "GNI"),
		array("13", "GPO"),
		array("24", "Index Fungorum"),
		array("34", "Index to Organism Names"),
		array("26", "Interim Reg. of Marine/Nonmarine Genera"),
		array("3", "ISBN"),
		array("2", "ISSN"),
		array("22", "ITIS"),
		array("36", "JSTOR"),
		array("14", "MARC001"),
		array("12", "NAL"),
		array("17", "NameBank"),
		array("23", "NCBI"),
		array("11", "NLM"),
		array("35", "OAI"),
		array("1", "OCLC"),
		array("37", "Soulsby"),
		array("33", "The International Plant Names Index"),
		array("8", "TL2"),
		array("32", "Tropicos"),
		array("25", "Union 4"),
		array("15", "VIAF"),
		array("21", "Wikispecies"),
		array("4", "WonderFetch"),
		array("27", "WoRMS"),
		array("29", "ZooBank"));
		
	$segment_languages = array(
	
		array("abk", "Abkhaz"),
		array("ace", "Achinese"),
		array("ach", "Acoli"),
		array("ada", "Adangme"),
		array("ady", "Adygei"),
		array("aar", "Afar"),
		array("afh", "Afrihili (Artificial language)"),
		array("afr", "Afrikaans"),
		array("afa", "Afroasiatic (Other)"),
		array("ain", "Ainu"),
		array("aka", "Akan"),
		array("akk", "Akkadian"),
		array("alb", "Albanian"),
		array("ale", "Aleut"),
		array("alg", "Algonquian (Other)"),
		array("-ajm", "Aljamía"),
		array("alt", "Altai"),
		array("tut", "Altaic (Other)"),
		array("amh", "Amharic"),
		array("anp", "Angika"),
		array("apa", "Apache languages"),
		array("ara", "Arabic"),
		array("arg", "Aragonese"),
		array("arc", "Aramaic"),
		array("arp", "Arapaho"),
		array("arw", "Arawak"),
		array("arm", "Armenian"),
		array("rup", "Aromanian"),
		array("art", "Artificial (Other)"),
		array("asm", "Assamese"),
		array("ath", "Athapascan (Other)"),
		array("aus", "Australian languages"),
		array("map", "Austronesian (Other)"),
		array("ava", "Avaric"),
		array("ave", "Avestan"),
		array("awa", "Awadhi"),
		array("aym", "Aymara"),
		array("aze", "Azerbaijani"),
		array("ast", "Bable"),
		array("ban", "Balinese"),
		array("bat", "Baltic (Other)"),
		array("bal", "Baluchi"),
		array("bam", "Bambara"),
		array("bai", "Bamileke languages"),
		array("bad", "Banda languages"),
		array("bnt", "Bantu (Other)"),
		array("bas", "Basa"),
		array("bak", "Bashkir"),
		array("baq", "Basque"),
		array("btk", "Batak"),
		array("bej", "Beja"),
		array("bel", "Belarusian"),
		array("bem", "Bemba"),
		array("ben", "Bengali"),
		array("ber", "Berber (Other)"),
		array("bho", "Bhojpuri"),
		array("bih", "Bihari (Other)"),
		array("bik", "Bikol"),
		array("byn", "Bilin"),
		array("bis", "Bislama"),
		array("zbl", "Blissymbolics"),
		array("bos", "Bosnian"),
		array("bra", "Braj"),
		array("bre", "Breton"),
		array("bug", "Bugis"),
		array("bul", "Bulgarian"),
		array("bua", "Buriat"),
		array("bur", "Burmese"),
		array("cad", "Caddo"),
		array("car", "Carib"),
		array("cat", "Catalan"),
		array("cau", "Caucasian (Other)"),
		array("ceb", "Cebuano"),
		array("cel", "Celtic (Other)"),
		array("cai", "Central American Indian (Other)"),
		array("chg", "Chagatai"),
		array("cmc", "Chamic languages"),
		array("cha", "Chamorro"),
		array("che", "Chechen"),
		array("chr", "Cherokee"),
		array("chy", "Cheyenne"),
		array("chb", "Chibcha"),
		array("chi", "Chinese"),
		array("chn", "Chinook jargon"),
		array("chp", "Chipewyan"),
		array("cho", "Choctaw"),
		array("chu", "Church Slavic"),
		array("chk", "Chuukese"),
		array("chv", "Chuvash"),
		array("cop", "Coptic"),
		array("cor", "Cornish"),
		array("cos", "Corsican"),
		array("cre", "Cree"),
		array("mus", "Creek"),
		array("crp", "Creoles and Pidgins (Other)"),
		array("cpe", "Creoles and Pidgins, English-based (Other)"),
		array("cpf", "Creoles and Pidgins, French-based (Other)"),
		array("cpp", "Creoles and Pidgins, Portuguese-based (Other)"),
		array("crh", "Crimean Tatar"),
		array("-scr", "Croatian"),
		array("hrv", "Croatian"),
		array("cus", "Cushitic (Other)"),
		array("cze", "Czech"),
		array("dak", "Dakota"),
		array("dan", "Danish"),
		array("dar", "Dargwa"),
		array("day", "Dayak"),
		array("del", "Delaware"),
		array("din", "Dinka"),
		array("div", "Divehi"),
		array("doi", "Dogri"),
		array("dgr", "Dogrib"),
		array("dra", "Dravidian (Other)"),
		array("dua", "Duala"),
		array("dut", "Dutch"),
		array("dum", "Dutch, Middle (ca. 1050-1350)"),
		array("dyu", "Dyula"),
		array("dzo", "Dzongkha"),
		array("frs", "East Frisian"),
		array("bin", "Edo"),
		array("efi", "Efik"),
		array("egy", "Egyptian"),
		array("eka", "Ekajuk"),
		array("elx", "Elamite"),
		array("eng", "English"),
		array("enm", "English, Middle (1100-1500)"),
		array("ang", "English, Old (ca. 450-1100)"),
		array("myv", "Erzya"),
		array("-esk", "Eskimo languages"),
		array("-esp", "Esperanto"),
		array("epo", "Esperanto"),
		array("est", "Estonian"),
		array("-eth", "Ethiopic"),
		array("gez", "Ethiopic"),
		array("ewe", "Ewe"),
		array("ewo", "Ewondo"),
		array("fan", "Fang"),
		array("fat", "Fanti"),
		array("-far", "Faroese"),
		array("fao", "Faroese"),
		array("fij", "Fijian"),
		array("fil", "Filipino"),
		array("fin", "Finnish"),
		array("fiu", "Finno-Ugrian (Other)"),
		array("fon", "Fon"),
		array("fre", "French"),
		array("frm", "French, Middle (ca. 1300-1600)"),
		array("fro", "French, Old (ca. 842-1300)"),
		array("-fri", "Frisian"),
		array("fry", "Frisian"),
		array("fur", "Friulian"),
		array("ful", "Fula"),
		array("-gag", "Galician"),
		array("glg", "Galician"),
		array("lug", "Ganda"),
		array("gay", "Gayo"),
		array("gba", "Gbaya"),
		array("geo", "Georgian"),
		array("ger", "German"),
		array("gmh", "German, Middle High (ca. 1050-1500)"),
		array("goh", "German, Old High (ca. 750-1050)"),
		array("gem", "Germanic (Other)"),
		array("gil", "Gilbertese"),
		array("gon", "Gondi"),
		array("gor", "Gorontalo"),
		array("got", "Gothic"),
		array("grb", "Grebo"),
		array("grc", "Greek, Ancient (to 1453)"),
		array("gre", "Greek, Modern (1453-)"),
		array("-gua", "Guarani"),
		array("grn", "Guarani"),
		array("guj", "Gujarati"),
		array("gwi", "Gwich'in"),
		array("gaa", "Gã"),
		array("hai", "Haida"),
		array("hat", "Haitian French Creole"),
		array("hau", "Hausa"),
		array("haw", "Hawaiian"),
		array("heb", "Hebrew"),
		array("her", "Herero"),
		array("hil", "Hiligaynon"),
		array("hin", "Hindi"),
		array("hmo", "Hiri Motu"),
		array("hit", "Hittite"),
		array("hmn", "Hmong"),
		array("hun", "Hungarian"),
		array("hup", "Hupa"),
		array("iba", "Iban"),
		array("ice", "Icelandic"),
		array("ido", "Ido"),
		array("ibo", "Igbo"),
		array("ijo", "Ijo"),
		array("ilo", "Iloko"),
		array("smn", "Inari Sami"),
		array("inc", "Indic (Other)"),
		array("ine", "Indo-European (Other)"),
		array("ind", "Indonesian"),
		array("inh", "Ingush"),
		array("-int", "Interlingua (International Auxiliary Language Association)"),
		array("ina", "Interlingua (International Auxiliary Language Association)"),
		array("ile", "Interlingue"),
		array("iku", "Inuktitut"),
		array("ipk", "Inupiaq"),
		array("ira", "Iranian (Other)"),
		array("-iri", "Irish"),
		array("gle", "Irish"),
		array("mga", "Irish, Middle (ca. 1100-1550)"),
		array("sga", "Irish, Old (to 1100)"),
		array("iro", "Iroquoian (Other)"),
		array("ita", "Italian"),
		array("jpn", "Japanese"),
		array("jav", "Javanese"),
		array("jrb", "Judeo-Arabic"),
		array("jpr", "Judeo-Persian"),
		array("kbd", "Kabardian"),
		array("kab", "Kabyle"),
		array("kac", "Kachin"),
		array("kal", "Kalâtdlisut"),
		array("kam", "Kamba"),
		array("kan", "Kannada"),
		array("kau", "Kanuri"),
		array("kaa", "Kara-Kalpak"),
		array("krc", "Karachay-Balkar"),
		array("krl", "Karelian"),
		array("kar", "Karen languages"),
		array("kas", "Kashmiri"),
		array("csb", "Kashubian"),
		array("kaw", "Kawi"),
		array("kaz", "Kazakh"),
		array("kha", "Khasi"),
		array("-cam", "Khmer"),
		array("khm", "Khmer"),
		array("khi", "Khoisan (Other)"),
		array("kho", "Khotanese"),
		array("kik", "Kikuyu"),
		array("kmb", "Kimbundu"),
		array("kin", "Kinyarwanda"),
		array("tlh", "Klingon (Artificial language)"),
		array("kom", "Komi"),
		array("kon", "Kongo"),
		array("kok", "Konkani"),
		array("kut", "Kootenai"),
		array("kor", "Korean"),
		array("kos", "Kosraean"),
		array("kpe", "Kpelle"),
		array("kro", "Kru (Other)"),
		array("kua", "Kuanyama"),
		array("kum", "Kumyk"),
		array("kur", "Kurdish"),
		array("kru", "Kurukh"),
		array("-kus", "Kusaie"),
		array("kir", "Kyrgyz"),
		array("lad", "Ladino"),
		array("lah", "Lahndā"),
		array("lam", "Lamba (Zambia and Congo)"),
		array("lao", "Lao"),
		array("lat", "Latin"),
		array("lav", "Latvian"),
		array("lez", "Lezgian"),
		array("lim", "Limburgish"),
		array("lin", "Lingala"),
		array("lit", "Lithuanian"),
		array("jbo", "Lojban (Artificial language)"),
		array("nds", "Low German"),
		array("dsb", "Lower Sorbian"),
		array("loz", "Lozi"),
		array("lub", "Luba-Katanga"),
		array("lua", "Luba-Lulua"),
		array("lui", "Luiseño"),
		array("smj", "Lule Sami"),
		array("lun", "Lunda"),
		array("luo", "Luo (Kenya and Tanzania)"),
		array("lus", "Lushai"),
		array("ltz", "Luxembourgish"),
		array("mas", "Maasai"),
		array("mac", "Macedonian"),
		array("mad", "Madurese"),
		array("mag", "Magahi"),
		array("mai", "Maithili"),
		array("mak", "Makasar"),
		array("-mla", "Malagasy"),
		array("mlg", "Malagasy"),
		array("may", "Malay"),
		array("mal", "Malayalam"),
		array("mlt", "Maltese"),
		array("mnc", "Manchu"),
		array("mdr", "Mandar"),
		array("man", "Mandingo"),
		array("mni", "Manipuri"),
		array("mno", "Manobo languages"),
		array("-max", "Manx"),
		array("glv", "Manx"),
		array("mao", "Maori"),
		array("arn", "Mapuche"),
		array("mar", "Marathi"),
		array("chm", "Mari"),
		array("mah", "Marshallese"),
		array("mwr", "Marwari"),
		array("myn", "Mayan languages"),
		array("men", "Mende"),
		array("mic", "Micmac"),
		array("min", "Minangkabau"),
		array("mwl", "Mirandese"),
		array("mis", "Miscellaneous languages"),
		array("moh", "Mohawk"),
		array("mdf", "Moksha"),
		array("-mol", "Moldavian"),
		array("mkh", "Mon-Khmer (Other)"),
		array("lol", "Mongo-Nkundu"),
		array("mon", "Mongolian"),
		array("mos", "Mooré"),
		array("mul", "Multiple languages"),
		array("mun", "Munda (Other)"),
		array("nqo", "N'Ko"),
		array("nah", "Nahuatl"),
		array("nau", "Nauru"),
		array("nav", "Navajo"),
		array("nbl", "Ndebele (South Africa)"),
		array("nde", "Ndebele (Zimbabwe)"),
		array("ndo", "Ndonga"),
		array("nap", "Neapolitan Italian"),
		array("nep", "Nepali"),
		array("new", "Newari"),
		array("nwc", "Newari, Old"),
		array("nia", "Nias"),
		array("nic", "Niger-Kordofanian (Other)"),
		array("ssa", "Nilo-Saharan (Other)"),
		array("niu", "Niuean"),
		array("zxx", "No linguistic content"),
		array("nog", "Nogai"),
		array("nai", "North American Indian (Other)"),
		array("frr", "North Frisian"),
		array("sme", "Northern Sami"),
		array("nso", "Northern Sotho"),
		array("nob", "Norwegian (Bokmål)"),
		array("nno", "Norwegian (Nynorsk)"),
		array("nor", "Norwegian"),
		array("nub", "Nubian languages"),
		array("nym", "Nyamwezi"),
		array("nya", "Nyanja"),
		array("nyn", "Nyankole"),
		array("nyo", "Nyoro"),
		array("nzi", "Nzima"),
		array("-lan", "Occitan (post 1500)"),
		array("oci", "Occitan (post-1500)"),
		array("xal", "Oirat"),
		array("oji", "Ojibwa"),
		array("non", "Old Norse"),
		array("peo", "Old Persian (ca. 600-400 B.C.)"),
		array("ori", "Oriya"),
		array("-gal", "Oromo"),
		array("orm", "Oromo"),
		array("osa", "Osage"),
		array("oss", "Ossetic"),
		array("oto", "Otomian languages"),
		array("pal", "Pahlavi"),
		array("pau", "Palauan"),
		array("pli", "Pali"),
		array("pam", "Pampanga"),
		array("pag", "Pangasinan"),
		array("pan", "Panjabi"),
		array("pap", "Papiamento"),
		array("paa", "Papuan (Other)"),
		array("per", "Persian"),
		array("phi", "Philippine (Other)"),
		array("phn", "Phoenician"),
		array("pon", "Pohnpeian"),
		array("pol", "Polish"),
		array("por", "Portuguese"),
		array("pra", "Prakrit languages"),
		array("pro", "Provençal (to 1500)"),
		array("pus", "Pushto"),
		array("que", "Quechua"),
		array("roh", "Raeto-Romance"),
		array("raj", "Rajasthani"),
		array("rap", "Rapanui"),
		array("rar", "Rarotongan"),
		array("roa", "Romance (Other)"),
		array("rom", "Romani"),
		array("rum", "Romanian"),
		array("run", "Rundi"),
		array("rus", "Russian"),
		array("sal", "Salishan languages"),
		array("sam", "Samaritan Aramaic"),
		array("-lap", "Sami"),
		array("smi", "Sami"),
		array("-sao", "Samoan"),
		array("smo", "Samoan"),
		array("sad", "Sandawe"),
		array("sag", "Sango (Ubangi Creole)"),
		array("san", "Sanskrit"),
		array("sat", "Santali"),
		array("srd", "Sardinian"),
		array("sas", "Sasak"),
		array("sco", "Scots"),
		array("gla", "Scottish Gaelic"),
		array("-gae", "Scottish Gaelix"),
		array("sel", "Selkup"),
		array("sem", "Semitic (Other)"),
		array("-scc", "Serbian"),
		array("srp", "Serbian"),
		array("srr", "Serer"),
		array("shn", "Shan"),
		array("-sho", "Shona"),
		array("sna", "Shona"),
		array("iii", "Sichuan Yi"),
		array("scn", "Sicilian Italian"),
		array("sid", "Sidamo"),
		array("sgn", "Sign languages"),
		array("bla", "Siksika"),
		array("snd", "Sindhi"),
		array("-snh", "Sinhalese"),
		array("sin", "Sinhalese"),
		array("sit", "Sino-Tibetan (Other)"),
		array("sio", "Siouan (Other)"),
		array("sms", "Skolt Sami"),
		array("den", "Slavey"),
		array("sla", "Slavic (Other)"),
		array("slo", "Slovak"),
		array("slv", "Slovenian"),
		array("sog", "Sogdian"),
		array("som", "Somali"),
		array("son", "Songhai"),
		array("snk", "Soninke"),
		array("wen", "Sorbian (Other)"),
		array("-sso", "Sotho"),
		array("sot", "Sotho"),
		array("sai", "South American Indian (Other)"),
		array("sma", "Southern Sami"),
		array("spa", "Spanish"),
		array("srn", "Sranan"),
		array("suk", "Sukuma"),
		array("sux", "Sumerian"),
		array("sun", "Sundanese"),
		array("sus", "Susu"),
		array("swa", "Swahili"),
		array("-swz", "Swazi"),
		array("ssw", "Swazi"),
		array("swe", "Swedish"),
		array("gsw", "Swiss German"),
		array("syc", "Syriac"),
		array("syr", "Syriac, Modern"),
		array("-tag", "Tagalog"),
		array("tgl", "Tagalog"),
		array("tah", "Tahitian"),
		array("tai", "Tai (Other)"),
		array("-taj", "Tajik"),
		array("tgk", "Tajik"),
		array("tmh", "Tamashek"),
		array("tam", "Tamil"),
		array("-tar", "Tatar"),
		array("tat", "Tatar"),
		array("tel", "Telugu"),
		array("tem", "Temne"),
		array("ter", "Terena"),
		array("tet", "Tetum"),
		array("tha", "Thai"),
		array("tib", "Tibetan"),
		array("tir", "Tigrinya"),
		array("tig", "Tigré"),
		array("tiv", "Tiv"),
		array("tli", "Tlingit"),
		array("tpi", "Tok Pisin"),
		array("tkl", "Tokelauan"),
		array("tog", "Tonga (Nyasa)"),
		array("ton", "Tongan"),
		array("-tru", "Truk"),
		array("tsi", "Tsimshian"),
		array("tso", "Tsonga"),
		array("-tsw", "Tswana"),
		array("tsn", "Tswana"),
		array("tum", "Tumbuka"),
		array("tup", "Tupi languages"),
		array("tur", "Turkish"),
		array("ota", "Turkish, Ottoman"),
		array("tuk", "Turkmen"),
		array("tvl", "Tuvaluan"),
		array("tyv", "Tuvinian"),
		array("twi", "Twi"),
		array("udm", "Udmurt"),
		array("uga", "Ugaritic"),
		array("uig", "Uighur"),
		array("ukr", "Ukrainian"),
		array("umb", "Umbundu"),
		array("und", "Undetermined"),
		array("hsb", "Upper Sorbian"),
		array("urd", "Urdu"),
		array("uzb", "Uzbek"),
		array("vai", "Vai"),
		array("ven", "Venda"),
		array("vie", "Vietnamese"),
		array("vol", "Volapük"),
		array("vot", "Votic"),
		array("wak", "Wakashan languages"),
		array("wln", "Walloon"),
		array("war", "Waray"),
		array("was", "Washoe"),
		array("wel", "Welsh"),
		array("him", "Western Pahari languages"),
		array("wal", "Wolayta"),
		array("wol", "Wolof"),
		array("xho", "Xhosa"),
		array("sah", "Yakut"),
		array("yao", "Yao (Africa)"),
		array("yap", "Yapese"),
		array("yid", "Yiddish"),
		array("yor", "Yoruba"),
		array("ypk", "Yupik languages"),
		array("znd", "Zande languages"),
		array("zap", "Zapotec"),
		array("zza", "Zaza"),
		array("zen", "Zenaga"),
		array("zha", "Zhuang"),
		array("zul", "Zulu"),
		array("zun", "Zuni")
	);
	function createOption($option) {
		return "<option value=\"{$option[0]}\">{$option[1]}</option>'";
	}
?>

<style type="text/css">
	#bhl-segments {line-height: 200%;}
	#bhl-segments textarea {height:30px;width: 80%;}

	#bhl-segments td label img,
	#bhl-segments td img {
		cursor: pointer;
		padding-bottom: 3px;
	}
	#bhl-segments td select {
		width: 100%;
		box-sizing: border-box;
	}
	#bhl-segments td input {
		width: 100%;
		box-sizing: border-box;
		border: 1px solid #000;
		padding: 1px 3px;
		height: 22px;
		vertical-align: top;
	}
	#bhl-segments td input#segment_author_last_name {
		width: 53%;
	}
	#bhl-segments td input#segment_author_first_name {
		width: 44%;
	}
	#bhl-segments td input#segment_author_start_date {
		width: 40%;
		text-align:center;
	}
	#bhl-segments td input#segment_author_end_date {
		width: 40%;
		text-align:center;
	}
	#bhl-segments td input:disabled,
	#bhl-segments td select:disabled {
		background-color: #f2f2f2;
	}
	#bhl-segments #segmentPages {
		margin-left: 10px;
		background-color: #B2D9B0;
		padding: 3px;
	}
	#bhl-segments #segmentWarning {
		color: #990000;
		display: none;
		float: right;
	}
	#bhl-segments .table-wrapper {
		padding: 0px !important;
	}
	#bhl-segments .list {
		vertical-align: top;
	}
	/* #bhl-segments .list ul {
		margin: 0;
	} */
	#bhl-segments .list ul a {
		color: #333;
		text-decoration: underline;
	}
	#bhl-segments .author-list {
		background-color: #FFFFFF;
		border: 1px solid rgb(169, 169, 169);
		height: 57px;
		overflow-y: scroll;
		padding: 3px 3px;
		margin: -6px 0 0 2px;
	}
	#bhl-segments .author-list li {
		margin: -5px 0 -5px 0px;
	}
	#bhl-segments .identifier-list li,
	#bhl-segments .keyword-list li {
		display: inline;
		float: left;
		padding-right: 10px;
	}
	
	#bhl-segments .remove-button {
		background: url(../images/icons/delete_grey.png) no-repeat;
		cursor: pointer;
		float: left;
		height: 16px;
		width: 16px;
		margin: 4px 4px 0 0;
	}
	
	#bhl-segments .remove-button:hover {
		background: url(../images/icons/delete.png) no-repeat;
	}

	#bhl-segments .bhl-icon {
		background: url(../images/icons/bhl.png) no-repeat;
		background-size: 20px;
		display: inline-block;
		height: 15px;
		margin-left: 4px;
		width: 20px;
	}
		
	#bhl-segments .yui-dt-col-extra {
		white-space:nowrap;
	}
	
	#bhl-segments .yui-ac {
		border: 0;
		width: 100%;
	}

	#btnToggleExtra { display: inline-block; }
	#btnToggleList.yui-button button { border-radius: 0; }
	#btnToggleExtra.yui-button button { 
    background: url(../images/icons/bhl.png) no-repeat;
    background-size: 24px;
    background-position: 3px 0px;	
  }

	/* styles for results container */
	.yui-skin-sam #bhl-segments .yui-ac-container {
			width:100%;
	}
	/* styles for result item */
	.yui-skin-sam #bhl-segments .yui-ac-content li {
			padding:2px 4px;line-height:1;
	}
	#authors_list {
		margin-top: -5px;
	}
</style>

<div id="bhl-segments">
	<table  cellspacing="0" cellpadding="3" width="100%">
		<tr>
			<td valign="top">
				<label for="segmentList">Segment:</label>
			</td>
			<td width="30%" valign="top">
				<select id="segmentList" onChange="SegmentComponent.selectionChanged(this);" >
					<option value="" selected>(select or add a segment)</option>
				</select>
			</td>
			<td width="70%">
				<img id="btnAddSegment" src="<?php echo $this->config->item('base_url'); ?>images/icons/add.png" onClick="SegmentComponent.addSegment();" class="icon" title="Add new segment">
				<img id="btnDeleteSegment" src="<?php echo $this->config->item('base_url'); ?>images/icons/delete.png" onClick="SegmentComponent.removeSegment();" class="icon" title="Remove segment">
				<img id="btnDeleteSegment" src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png" onClick="SegmentComponent.removeAllSegments();" class="icon" title="Remove all segments">
				<span id="segmentMessages">

					<span id="segmentPages"></span>
					<span id="segmentWarning">
						Segment page selection has changed. Update now:
						<img id="btnupdateSegmentPages" src="<?php echo $this->config->item('base_url'); ?>images/icons/pages_refresh.png" onClick="SegmentComponent.updatePages(this);" class="icon" title="Update segment pages to currently selected pages.">
					</span>
				</div>
			</td>
		</tr>
	</table>

	<hr>

	<table border="0" cellspacing="0" cellpadding="3" width="100%">
		<tr>
			<td colspan="2" class="table-wrapper" valign="top">
				<table border="0" cellspacing="0" cellpadding="0" width="100%">
					<tr>
						<td colspan="8" class="table-wrapper">
							<table border="0" cellspacing="0" cellpadding="0" width="100%">
								<tr>
									<td><label for="segment_title">Segment&nbsp;Title:</label></td>
									<td width="34%">
										<input type="text" id="segment_title" onChange="SegmentComponent.metadataChanged(this);" title="Segment Title" placeholder="segment title" maxlength="2000" disabled>
									</td>
									<td><label for="segment_translated_title">Translated&nbsp;Title:</label></td>
									<td width="34%">
										<input type="text" id="segment_translated_title" onChange="SegmentComponent.metadataChanged(this);" title="Translated Segment Title"  placeholder="translated title" maxlength="2000">
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td><label for="segment_volume">Volume:</label></td>
						<td width="25%">
							<input type="text" id="segment_volume" onChange="SegmentComponent.metadataChanged(this);" title="Segment Volume" maxlength="100" placeholder="##" disabled>
						</td>
						<td><label for="segment_issue">Issue:</label></td>
						<td width="25%">
							<input type="text" id="segment_issue" onChange="SegmentComponent.metadataChanged(this);" title="Segment Issue" maxlength="100" placeholder="##" disabled>
						</td>
						<td><label for="segment_series">Series:</label></td>
						<td width="25%">
							<input type="text" id="segment_series" onChange="SegmentComponent.metadataChanged(this);" title="Segment Series" maxlength="100" placeholder="##" disabled>
						</td>
						<td><label for="segment_date">Date:</label></td>
						<td width="25%">
							<input type="text" id="segment_date" onChange="SegmentComponent.metadataChanged(this);" title="Segment Date" maxlength="20" placeholder="YYYY" disabled>
						</td>
					</tr>	
					<tr>
						<td colspan="8" class="table-wrapper">
							<table border="0" cellspacing="0" cellpadding="0" width="100%">
								<td><label for="segment_genre">Genre:</label></td>
								<td width="25%">
									<select id="segment_genre" onChange="SegmentComponent.metadataChanged(this);"  disabled>
										<option value=""></option>
										<?php echo(implode('', array_map('createOption', $segment_genres))); ?>
									</select>
								</td>
								<td><label for="segment_language">Language:</label></td>
								<td width="25%">
									<select id="segment_language" onChange="SegmentComponent.metadataChanged(this);"  disabled>
										<option value=""></option>
										<?php echo(implode('', array_map('createOption', $segment_languages))); ?>
									</select>
								</td>
								<td><label for="segment_doi">DOI:</label></td>
								<td width="30%">
									<input type="text" id="segment_doi" onChange="SegmentComponent.metadataChanged(this);" title="Segment DOI" maxlength="50"  placeholder="10.1234/abc.def.123" disabled>
								</td>
							</table>							
						</td>
					</tr>				
				</table>
			</td>
			<td width="40%" valign="top">
				<table cellspacing="0" cellpadding="0" width="100%" id="authors_list">
					<tr>
						<td id="segment_authors" colspan="6">
							<label for="segment_authors_list">Authors: 
								<img style="display: none" src="<?php echo $this->config->item('base_url'); ?>images/icons/add.png" id="btnShowAuthorDlg" class="icon" onClick="AuthorComponent.showDialog(this)" title="Add new author">
								<img style="display: none" src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png" id="btnClearAuthorType" onClick="AuthorComponent.removeAll()" class="icon" onMouseOver="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png';" onMouseOut="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png';" title="Remove all authors"></label>
							</label>
						</td>
					</tr>
					<tr>
						<td class="list" colspan="6">
							<ul id="segment_authors_list" class="author-list"></ul>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<div id="dlgSegmentPages" style="display:none;margin-top:-200px">
		<div style="border: 1px solid #999; padding: 5px; line-height: 1.7; margin: 0 0 5px;">

		</div>
	</div>
	
	<div id="dlgAuthor" style="display:none;margin-top:-200px">
		<div style="border: 1px solid #999; padding: 5px; line-height: 1.7; margin: 0 0 5px;">
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td width="70%">
						<strong>Name:</strong> <a id="segment_author_source" target="_blank" style="visibility: hidden;"></a>
						<div id="myAutoComplete">
							<input type="text" id="segment_author_last_name" placeholder="last name" tabindex="50" maxlength="150">
							<input type="text" id="segment_author_first_name" placeholder="first name" tabindex="52" maxlength="150">
							<div id="segment_author_name_autocomplete"></div>
						</div>
					</td>
					<td width="30%">
						<strong>Dates:</strong>
						<div>
						<input type="text" id="segment_author_start_date" placeholder="yyyy" tabindex="53" size="5">&mdash;<input type="text" id="segment_author_end_date" placeholder="yyyy" tabindex="54" size="5">
						</div>
					</td>
				</tr>
			</table>
			
			<table border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<strong>Identifier Type:</strong> <br>
						<select id="segment_author_identifier_type" tabindex="54">
							<option value=""></option>
							<?php echo(implode('', array_map('createOption', $segment_identifiers))); ?>
						</select>
					</td>
					<td>
						<strong>Value:</strong>
						<input type="text" id="segment_author_identifier_value" tabindex="55" maxlength="125">
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
