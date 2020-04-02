<?php
	$segment_genres = array(
		array("1", "Article"),
		array("2", "Book"),
		array("3", "BookItem"),
		array("4", "Chapter"),
		array("8", "Conference"),
		array("6", "Issue"),
		array("5", "Journal"),
		array("14", "Letter"),
		array("9", "Preprint"),
		array("7", "Proceeding"),
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
		array("aar", "Afar"),
		array("abk", "Abkhaz"),
		array("ace", "Achinese"),
		array("ach", "Acoli"),
		array("ada", "Adangme"),
		array("ady", "Adygei"),
		array("afa", "Afroasiatic (Other)"),
		array("afh", "Afrihili (Artificial language)"),
		array("afr", "Afrikaans"),
		array("ain", "Ainu"),
		array("-ajm", "Aljamía"),
		array("aka", "Akan"),
		array("akk", "Akkadian"),
		array("alb", "Albanian"),
		array("ale", "Aleut"),
		array("alg", "Algonquian (Other)"),
		array("alt", "Altai"),
		array("amh", "Amharic"),
		array("ang", "English, Old (ca. 450-1100)"),
		array("anp", "Angika"),
		array("apa", "Apache languages"),
		array("ara", "Arabic"),
		array("arc", "Aramaic"),
		array("arg", "Aragonese"),
		array("arm", "Armenian"),
		array("arn", "Mapuche"),
		array("arp", "Arapaho"),
		array("art", "Artificial (Other)"),
		array("arw", "Arawak"),
		array("asm", "Assamese"),
		array("ast", "Bable"),
		array("ath", "Athapascan (Other)"),
		array("aus", "Australian languages"),
		array("ava", "Avaric"),
		array("ave", "Avestan"),
		array("awa", "Awadhi"),
		array("aym", "Aymara"),
		array("aze", "Azerbaijani"),
		array("bad", "Banda languages"),
		array("bai", "Bamileke languages"),
		array("bak", "Bashkir"),
		array("bal", "Baluchi"),
		array("bam", "Bambara"),
		array("ban", "Balinese"),
		array("baq", "Basque"),
		array("bas", "Basa"),
		array("bat", "Baltic (Other)"),
		array("bej", "Beja"),
		array("bel", "Belarusian"),
		array("bem", "Bemba"),
		array("ben", "Bengali"),
		array("ber", "Berber (Other)"),
		array("bho", "Bhojpuri"),
		array("bih", "Bihari (Other)"),
		array("bik", "Bikol"),
		array("bin", "Edo"),
		array("bis", "Bislama"),
		array("bla", "Siksika"),
		array("bnt", "Bantu (Other)"),
		array("bos", "Bosnian"),
		array("bra", "Braj"),
		array("bre", "Breton"),
		array("btk", "Batak"),
		array("bua", "Buriat"),
		array("bug", "Bugis"),
		array("bul", "Bulgarian"),
		array("bur", "Burmese"),
		array("byn", "Bilin"),
		array("cad", "Caddo"),
		array("cai", "Central American Indian (Other)"),
		array("-cam", "Khmer"),
		array("car", "Carib"),
		array("cat", "Catalan"),
		array("cau", "Caucasian (Other)"),
		array("ceb", "Cebuano"),
		array("cel", "Celtic (Other)"),
		array("cha", "Chamorro"),
		array("chb", "Chibcha"),
		array("che", "Chechen"),
		array("chg", "Chagatai"),
		array("chi", "Chinese"),
		array("chk", "Chuukese"),
		array("chm", "Mari"),
		array("chn", "Chinook jargon"),
		array("cho", "Choctaw"),
		array("chp", "Chipewyan"),
		array("chr", "Cherokee"),
		array("chu", "Church Slavic"),
		array("chv", "Chuvash"),
		array("chy", "Cheyenne"),
		array("cmc", "Chamic languages"),
		array("cop", "Coptic"),
		array("cor", "Cornish"),
		array("cos", "Corsican"),
		array("cpe", "Creoles and Pidgins, English-based (Other)"),
		array("cpf", "Creoles and Pidgins, French-based (Other)"),
		array("cpp", "Creoles and Pidgins, Portuguese-based (Other)"),
		array("cre", "Cree"),
		array("crh", "Crimean Tatar"),
		array("crp", "Creoles and Pidgins (Other)"),
		array("csb", "Kashubian"),
		array("cus", "Cushitic (Other)"),
		array("cze", "Czech"),
		array("dak", "Dakota"),
		array("dan", "Danish"),
		array("dar", "Dargwa"),
		array("day", "Dayak"),
		array("del", "Delaware"),
		array("den", "Slavey"),
		array("dgr", "Dogrib"),
		array("din", "Dinka"),
		array("div", "Divehi"),
		array("doi", "Dogri"),
		array("dra", "Dravidian (Other)"),
		array("dsb", "Lower Sorbian"),
		array("dua", "Duala"),
		array("dum", "Dutch, Middle (ca. 1050-1350)"),
		array("dut", "Dutch"),
		array("dyu", "Dyula"),
		array("dzo", "Dzongkha"),
		array("efi", "Efik"),
		array("egy", "Egyptian"),
		array("eka", "Ekajuk"),
		array("elx", "Elamite"),
		array("eng", "English"),
		array("enm", "English, Middle (1100-1500)"),
		array("epo", "Esperanto"),
		array("-esk", "Eskimo languages"),
		array("-esp", "Esperanto"),
		array("est", "Estonian"),
		array("-eth", "Ethiopic"),
		array("ewe", "Ewe"),
		array("ewo", "Ewondo"),
		array("fan", "Fang"),
		array("fao", "Faroese"),
		array("-far", "Faroese"),
		array("fat", "Fanti"),
		array("fij", "Fijian"),
		array("fil", "Filipino"),
		array("fin", "Finnish"),
		array("fiu", "Finno-Ugrian (Other)"),
		array("fon", "Fon"),
		array("fre", "French"),
		array("-fri", "Frisian"),
		array("frm", "French, Middle (ca. 1300-1600)"),
		array("fro", "French, Old (ca. 842-1300)"),
		array("frr", "North Frisian"),
		array("frs", "East Frisian"),
		array("fry", "Frisian"),
		array("ful", "Fula"),
		array("fur", "Friulian"),
		array("gaa", "Gã"),
		array("-gae", "Scottish Gaelix"),
		array("-gag", "Galician"),
		array("-gal", "Oromo"),
		array("gay", "Gayo"),
		array("gba", "Gbaya"),
		array("gem", "Germanic (Other)"),
		array("geo", "Georgian"),
		array("ger", "German"),
		array("gez", "Ethiopic"),
		array("gil", "Gilbertese"),
		array("gla", "Scottish Gaelic"),
		array("gle", "Irish"),
		array("glg", "Galician"),
		array("glv", "Manx"),
		array("gmh", "German, Middle High (ca. 1050-1500)"),
		array("goh", "German, Old High (ca. 750-1050)"),
		array("gon", "Gondi"),
		array("gor", "Gorontalo"),
		array("got", "Gothic"),
		array("grb", "Grebo"),
		array("grc", "Greek, Ancient (to 1453)"),
		array("gre", "Greek, Modern (1453-)"),
		array("grn", "Guarani"),
		array("gsw", "Swiss German"),
		array("-gua", "Guarani"),
		array("guj", "Gujarati"),
		array("gwi", "Gwich'in"),
		array("hai", "Haida"),
		array("hat", "Haitian French Creole"),
		array("hau", "Hausa"),
		array("haw", "Hawaiian"),
		array("heb", "Hebrew"),
		array("her", "Herero"),
		array("hil", "Hiligaynon"),
		array("him", "Western Pahari languages"),
		array("hin", "Hindi"),
		array("hit", "Hittite"),
		array("hmn", "Hmong"),
		array("hmo", "Hiri Motu"),
		array("hrv", "Croatian"),
		array("hsb", "Upper Sorbian"),
		array("hun", "Hungarian"),
		array("hup", "Hupa"),
		array("iba", "Iban"),
		array("ibo", "Igbo"),
		array("ice", "Icelandic"),
		array("ido", "Ido"),
		array("iii", "Sichuan Yi"),
		array("ijo", "Ijo"),
		array("iku", "Inuktitut"),
		array("ile", "Interlingue"),
		array("ilo", "Iloko"),
		array("ina", "Interlingua (International Auxiliary Language Association)"),
		array("inc", "Indic (Other)"),
		array("ind", "Indonesian"),
		array("ine", "Indo-European (Other)"),
		array("inh", "Ingush"),
		array("-int", "Interlingua (International Auxiliary Language Association)"),
		array("ipk", "Inupiaq"),
		array("ira", "Iranian (Other)"),
		array("-iri", "Irish"),
		array("iro", "Iroquoian (Other)"),
		array("ita", "Italian"),
		array("jav", "Javanese"),
		array("jbo", "Lojban (Artificial language)"),
		array("jpn", "Japanese"),
		array("jpr", "Judeo-Persian"),
		array("jrb", "Judeo-Arabic"),
		array("kaa", "Kara-Kalpak"),
		array("kab", "Kabyle"),
		array("kac", "Kachin"),
		array("kal", "Kalâtdlisut"),
		array("kam", "Kamba"),
		array("kan", "Kannada"),
		array("kar", "Karen languages"),
		array("kas", "Kashmiri"),
		array("kau", "Kanuri"),
		array("kaw", "Kawi"),
		array("kaz", "Kazakh"),
		array("kbd", "Kabardian"),
		array("kha", "Khasi"),
		array("khi", "Khoisan (Other)"),
		array("khm", "Khmer"),
		array("kho", "Khotanese"),
		array("kik", "Kikuyu"),
		array("kin", "Kinyarwanda"),
		array("kir", "Kyrgyz"),
		array("kmb", "Kimbundu"),
		array("kok", "Konkani"),
		array("kom", "Komi"),
		array("kon", "Kongo"),
		array("kor", "Korean"),
		array("kos", "Kosraean"),
		array("kpe", "Kpelle"),
		array("krc", "Karachay-Balkar"),
		array("krl", "Karelian"),
		array("kro", "Kru (Other)"),
		array("kru", "Kurukh"),
		array("kua", "Kuanyama"),
		array("kum", "Kumyk"),
		array("kur", "Kurdish"),
		array("-kus", "Kusaie"),
		array("kut", "Kootenai"),
		array("lad", "Ladino"),
		array("lah", "Lahndā"),
		array("lam", "Lamba (Zambia and Congo)"),
		array("-lan", "Occitan (post 1500)"),
		array("lao", "Lao"),
		array("-lap", "Sami"),
		array("lat", "Latin"),
		array("lav", "Latvian"),
		array("lez", "Lezgian"),
		array("lim", "Limburgish"),
		array("lin", "Lingala"),
		array("lit", "Lithuanian"),
		array("lol", "Mongo-Nkundu"),
		array("loz", "Lozi"),
		array("ltz", "Luxembourgish"),
		array("lua", "Luba-Lulua"),
		array("lub", "Luba-Katanga"),
		array("lug", "Ganda"),
		array("lui", "Luiseño"),
		array("lun", "Lunda"),
		array("luo", "Luo (Kenya and Tanzania)"),
		array("lus", "Lushai"),
		array("mac", "Macedonian"),
		array("mad", "Madurese"),
		array("mag", "Magahi"),
		array("mah", "Marshallese"),
		array("mai", "Maithili"),
		array("mak", "Makasar"),
		array("mal", "Malayalam"),
		array("man", "Mandingo"),
		array("mao", "Maori"),
		array("map", "Austronesian (Other)"),
		array("mar", "Marathi"),
		array("mas", "Maasai"),
		array("-max", "Manx"),
		array("may", "Malay"),
		array("mdf", "Moksha"),
		array("mdr", "Mandar"),
		array("men", "Mende"),
		array("mga", "Irish, Middle (ca. 1100-1550)"),
		array("mic", "Micmac"),
		array("min", "Minangkabau"),
		array("mis", "Miscellaneous languages"),
		array("mkh", "Mon-Khmer (Other)"),
		array("-mla", "Malagasy"),
		array("mlg", "Malagasy"),
		array("mlt", "Maltese"),
		array("mnc", "Manchu"),
		array("mni", "Manipuri"),
		array("mno", "Manobo languages"),
		array("moh", "Mohawk"),
		array("-mol", "Moldavian"),
		array("mon", "Mongolian"),
		array("mos", "Mooré"),
		array("mul", "Multiple languages"),
		array("mun", "Munda (Other)"),
		array("mus", "Creek"),
		array("mwl", "Mirandese"),
		array("mwr", "Marwari"),
		array("myn", "Mayan languages"),
		array("myv", "Erzya"),
		array("nah", "Nahuatl"),
		array("nai", "North American Indian (Other)"),
		array("nap", "Neapolitan Italian"),
		array("nau", "Nauru"),
		array("nav", "Navajo"),
		array("nbl", "Ndebele (South Africa)"),
		array("nde", "Ndebele (Zimbabwe)"),
		array("ndo", "Ndonga"),
		array("nds", "Low German"),
		array("nep", "Nepali"),
		array("new", "Newari"),
		array("nia", "Nias"),
		array("nic", "Niger-Kordofanian (Other)"),
		array("niu", "Niuean"),
		array("nno", "Norwegian (Nynorsk)"),
		array("nob", "Norwegian (Bokmål)"),
		array("nog", "Nogai"),
		array("non", "Old Norse"),
		array("nor", "Norwegian"),
		array("nqo", "N'Ko"),
		array("nso", "Northern Sotho"),
		array("nub", "Nubian languages"),
		array("nwc", "Newari, Old"),
		array("nya", "Nyanja"),
		array("nym", "Nyamwezi"),
		array("nyn", "Nyankole"),
		array("nyo", "Nyoro"),
		array("nzi", "Nzima"),
		array("oci", "Occitan (post-1500)"),
		array("oji", "Ojibwa"),
		array("ori", "Oriya"),
		array("orm", "Oromo"),
		array("osa", "Osage"),
		array("oss", "Ossetic"),
		array("ota", "Turkish, Ottoman"),
		array("oto", "Otomian languages"),
		array("paa", "Papuan (Other)"),
		array("pag", "Pangasinan"),
		array("pal", "Pahlavi"),
		array("pam", "Pampanga"),
		array("pan", "Panjabi"),
		array("pap", "Papiamento"),
		array("pau", "Palauan"),
		array("peo", "Old Persian (ca. 600-400 B.C.)"),
		array("per", "Persian"),
		array("phi", "Philippine (Other)"),
		array("phn", "Phoenician"),
		array("pli", "Pali"),
		array("pol", "Polish"),
		array("pon", "Pohnpeian"),
		array("por", "Portuguese"),
		array("pra", "Prakrit languages"),
		array("pro", "Provençal (to 1500)"),
		array("pus", "Pushto"),
		array("que", "Quechua"),
		array("raj", "Rajasthani"),
		array("rap", "Rapanui"),
		array("rar", "Rarotongan"),
		array("roa", "Romance (Other)"),
		array("roh", "Raeto-Romance"),
		array("rom", "Romani"),
		array("rum", "Romanian"),
		array("run", "Rundi"),
		array("rup", "Aromanian"),
		array("rus", "Russian"),
		array("sad", "Sandawe"),
		array("sag", "Sango (Ubangi Creole)"),
		array("sah", "Yakut"),
		array("sai", "South American Indian (Other)"),
		array("sal", "Salishan languages"),
		array("sam", "Samaritan Aramaic"),
		array("san", "Sanskrit"),
		array("-sao", "Samoan"),
		array("sas", "Sasak"),
		array("sat", "Santali"),
		array("-scc", "Serbian"),
		array("scn", "Sicilian Italian"),
		array("sco", "Scots"),
		array("-scr", "Croatian"),
		array("sel", "Selkup"),
		array("sem", "Semitic (Other)"),
		array("sga", "Irish, Old (to 1100)"),
		array("sgn", "Sign languages"),
		array("shn", "Shan"),
		array("-sho", "Shona"),
		array("sid", "Sidamo"),
		array("sin", "Sinhalese"),
		array("sio", "Siouan (Other)"),
		array("sit", "Sino-Tibetan (Other)"),
		array("sla", "Slavic (Other)"),
		array("slo", "Slovak"),
		array("slv", "Slovenian"),
		array("sma", "Southern Sami"),
		array("sme", "Northern Sami"),
		array("smi", "Sami"),
		array("smj", "Lule Sami"),
		array("smn", "Inari Sami"),
		array("smo", "Samoan"),
		array("sms", "Skolt Sami"),
		array("sna", "Shona"),
		array("snd", "Sindhi"),
		array("-snh", "Sinhalese"),
		array("snk", "Soninke"),
		array("sog", "Sogdian"),
		array("som", "Somali"),
		array("son", "Songhai"),
		array("sot", "Sotho"),
		array("spa", "Spanish"),
		array("srd", "Sardinian"),
		array("srn", "Sranan"),
		array("srp", "Serbian"),
		array("srr", "Serer"),
		array("ssa", "Nilo-Saharan (Other)"),
		array("-sso", "Sotho"),
		array("ssw", "Swazi"),
		array("suk", "Sukuma"),
		array("sun", "Sundanese"),
		array("sus", "Susu"),
		array("sux", "Sumerian"),
		array("swa", "Swahili"),
		array("swe", "Swedish"),
		array("-swz", "Swazi"),
		array("syc", "Syriac"),
		array("syr", "Syriac, Modern"),
		array("-tag", "Tagalog"),
		array("tah", "Tahitian"),
		array("tai", "Tai (Other)"),
		array("-taj", "Tajik"),
		array("tam", "Tamil"),
		array("-tar", "Tatar"),
		array("tat", "Tatar"),
		array("tel", "Telugu"),
		array("tem", "Temne"),
		array("ter", "Terena"),
		array("tet", "Tetum"),
		array("tgk", "Tajik"),
		array("tgl", "Tagalog"),
		array("tha", "Thai"),
		array("tib", "Tibetan"),
		array("tig", "Tigré"),
		array("tir", "Tigrinya"),
		array("tiv", "Tiv"),
		array("tkl", "Tokelauan"),
		array("tlh", "Klingon (Artificial language)"),
		array("tli", "Tlingit"),
		array("tmh", "Tamashek"),
		array("tog", "Tonga (Nyasa)"),
		array("ton", "Tongan"),
		array("tpi", "Tok Pisin"),
		array("-tru", "Truk"),
		array("tsi", "Tsimshian"),
		array("tsn", "Tswana"),
		array("tso", "Tsonga"),
		array("-tsw", "Tswana"),
		array("tuk", "Turkmen"),
		array("tum", "Tumbuka"),
		array("tup", "Tupi languages"),
		array("tur", "Turkish"),
		array("tut", "Altaic (Other)"),
		array("tvl", "Tuvaluan"),
		array("twi", "Twi"),
		array("tyv", "Tuvinian"),
		array("udm", "Udmurt"),
		array("uga", "Ugaritic"),
		array("uig", "Uighur"),
		array("ukr", "Ukrainian"),
		array("umb", "Umbundu"),
		array("und", "Undetermined"),
		array("urd", "Urdu"),
		array("uzb", "Uzbek"),
		array("vai", "Vai"),
		array("ven", "Venda"),
		array("vie", "Vietnamese"),
		array("vol", "Volapük"),
		array("vot", "Votic"),
		array("wak", "Wakashan languages"),
		array("wal", "Wolayta"),
		array("war", "Waray"),
		array("was", "Washoe"),
		array("wel", "Welsh"),
		array("wen", "Sorbian (Other)"),
		array("wln", "Walloon"),
		array("wol", "Wolof"),
		array("xal", "Oirat"),
		array("xho", "Xhosa"),
		array("yao", "Yao (Africa)"),
		array("yap", "Yapese"),
		array("yid", "Yiddish"),
		array("yor", "Yoruba"),
		array("ypk", "Yupik languages"),
		array("zap", "Zapotec"),
		array("zbl", "Blissymbolics"),
		array("zen", "Zenaga"),
		array("zha", "Zhuang"),
		array("znd", "Zande languages"),
		array("zul", "Zulu"),
		array("zun", "Zuni"),
		array("zxx", "No linguistic content"),
		array("zza", "Zaza"));
		
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
	#bhl-segments td input,
	#bhl-segments td select {
		width: 100%;
		box-sizing: border-box;
		-webkit-box-sizing:border-box;
		-moz-box-sizing: border-box;
	}
	#bhl-segments td input:disabled,
	#bhl-segments td select:disabled {
		background-color: #f2f2f2;
	}
	#bhl-segments #segmentPages {
		float: right;
	}
	#bhl-segments #segmentWarning {
		color: red;
		display: none;
		float: right;
	}
	#bhl-segments .table-wrapper {
		padding: 0px !important;
	}
	#bhl-segments .list {
		vertical-align: top;
	}
	#bhl-segments .list ul {
		margin: 0;
	}
	#bhl-segments .list ul a {
		color: #333;
		text-decoration: underline;
	}
	#bhl-segments .author-list {
		background-color: #FFFFFF;
		border: 1px solid rgb(169, 169, 169);
		height: 68px;
		overflow-y: scroll;
		padding: 5px 3px;
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
	
	.yui-skin-sam .yui-ac-container,
	.yui-skin-sam .yui-ac-content ul {
		width: 100%;
	}
	#bhl-segments .yui-skin-sam .yui-ac-content {
		width: 100%;
		border: 1px solid #808080;
	}

	#btnToggleExtra { display: inline-block; }
	#btnToggleList.yui-button button { border-radius: 0; }
	#btnToggleExtra.yui-button button { 
    background: url(../images/icons/bhl.png) no-repeat;
    background-size: 24px;
    background-position: 3px 0px;	
  }
</style>

<div id="bhl-segments">
	<table border="0" cellspacing="0" cellpadding="3" width="100%">
		<tr>
			<td valign="top">
				<label for="segmentList">Segment:</label>
			</td>
			<td width="30%" valign="top">
				<select id="segmentList" onChange="SegmentComponent.selectionChanged(this);" >
					<option value="" selected></option>
				</select>
			</td>
			<td width="70%">
				<img id="btnAddSegment" src="<?php echo $this->config->item('base_url'); ?>images/icons/add.png" onClick="SegmentComponent.addSegment();" class="icon" title="Add new segment">
				<img id="btnDeleteSegment" src="<?php echo $this->config->item('base_url'); ?>images/icons/delete.png" onClick="SegmentComponent.removeSegment();" class="icon" title="Remove segment">
				<img id="btnDeleteSegment" src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png" onClick="SegmentComponent.removeAllSegments();" class="icon" title="Remove all segments">
				<span id="segmentMessages">

					<span id="segmentPages"></span>
					<span id="segmentWarning">
						<img  src="<?php echo $this->config->item('base_url'); ?>images/icons/error.png" class="icon">
						WARNING: Selected pages do not match segment pages.
						<img id="btnupdateSegmentPages" src="<?php echo $this->config->item('base_url'); ?>images/icons/pages_refresh.png" onClick="SegmentComponent.updatePages(this);" class="icon" title="Update pages for segment">
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
									<td><label for="segment_title">Segment Title:</label></td>
									<td width="35%">
										<input type="text" id="segment_title" onChange="SegmentComponent.metadataChanged(this);" title="Segment Title" disabled>
									</td>
									<td><label for="segment_translated_title">Translated Title:</label></td>
									<td width="35%">
										<input type="text" id="segment_translated_title" onChange="SegmentComponent.metadataChanged(this);" title="Translated Segment Title" disabled>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td><label for="segment_volume">Volume:</label></td>
						<td width="25%">
							<input type="text" id="segment_volume" onChange="SegmentComponent.metadataChanged(this);" title="Segment Volume" disabled>
						</td>
						<td><label for="segment_issue">Issue:</label></td>
						<td width="25%">
							<input type="text" id="segment_issue" onChange="SegmentComponent.metadataChanged(this);" title="Segment Issue" disabled>
						</td>
						<td><label for="segment_series">Series:</label></td>
						<td width="25%">
							<input type="text" id="segment_series" onChange="SegmentComponent.metadataChanged(this);" title="Segment Series" disabled>
						</td>
						<td><label for="segment_date">Date:</label></td>
						<td width="25%">
							<input type="text" id="segment_date" onChange="SegmentComponent.metadataChanged(this);" title="Segment Date" disabled>
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
									<input type="text" id="segment_doi" onChange="SegmentComponent.metadataChanged(this);" title="Segment DOI" disabled>
								</td>
							</table>							
						</td>
					</tr>				
				</table>
			</td>
			<td width="40%" valign="top">
				<table border="0" cellspacing="0" cellpadding="0" width="100%">
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
						<strong>Name:</strong> <a ID="segment_author_source" target="_blank" style="visibility: hidden;"></a>
						<div>
							<input type="text" id="segment_author_name">
							<div id="segment_author_name_autocomplete"></div>
						</div>
					</td>
					<td width="30%">
						<strong>Dates:</strong>
						<input type="text" id="segment_author_dates">
					</td>
				</tr>
			</table>
			
			<table border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<strong>Identifier Type:</strong> <br>
						<select id="segment_author_identifier_type">
							<option value=""></option>
							<?php echo(implode('', array_map('createOption', $segment_identifiers))); ?>
						</select>
					</td>
					<td>
						<strong>Value:</strong>
						<input type="text" id="segment_author_identifier_value">
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
