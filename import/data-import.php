<?php
ini_set('memory_limit', '768M');
print("Starting!\n");

global $user;
$original_user = $user;
$user = user_load(1);

$datas = array();

function get_files() {
	system("rm -f import/tmp/zips/*");
	system("rm -f import/tmp/xmls/*");

	$files = array(
		// Itinéraires touristiques routiers :
        //"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=491",

        // Tous les itinéraires
        "http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=1327",

		// Hôtellerie :
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=545",

		// Restauration :
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=469",

		// Patrimoine naturel :
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=465",

		// Patrimoine culturel :
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=468",

		// Loisirs :
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=466",

		// Fêtes et manifestations :
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=820",

		// Hôtellerie de plein air :
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=464",

		// Hébergements locatifs :
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=471",

		// Dégustation :
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=461",

		// Organismes : brocantes / boutiques…
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=467",

		// Produits touristiques :
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=544",

		// Trucs
		"http://JNOV:MY3yLUVaME@www.agit.tourisme-gers.com/index.php?sAuthType=Basic&tg=addon/tourisme/export&id=542",
		);

	foreach ($files as $file) {
		print("   ==== Grabbing $file ====\n");
		unlink("import/tmp/zips/f.zip");
		system("wget -q -O import/tmp/zips/f.zip '".$file."'");

		if (file_exists("import/tmp/zips/f.zip")) {
			$zip = new ZipArchive;
			if ($zip->open("import/tmp/zips/f.zip")) {
				$zip->extractTo("import/tmp/xmls/");
			}

		}
	}
}

function xpathstring($x, $path) {
	$v = $x->xpath($path);
	return (string)$v[0];
}

function parse_xml($f) {
	global $datas;

	$xml = new SimpleXMLElement(file_get_contents($f));

	$data = new stdClass;
	$data->id = $xml->xpath("/tif:OI/tif:DublinCore/dc:identifier");
	
	// pour n'importer qu'un node, à commenter pour tout importer -> toutes les infos en bas de page
	//if ((string)$data->id[0] != "32.FM.157.109494.10") { return; }
	
	$data->name = $xml->xpath("/tif:OI/tif:DublinCore/dc:title");
	
	$data->cartegmap = $xml->xpath("/tif:OI/tif:DescriptionsComplementaires/tif:DetailDescriptionComplementaire/tif:Description[@type='16.02.02']");

	$data->deschtml2 = $xml->xpath("/tif:OI/tif:DescriptionsComplementaires/tif:DetailDescriptionComplementaire[@type='16.02.08']/tif:Description");
	
	$data->deschtml3 = $xml->xpath("/tif:OI/tif:DescriptionsComplementaires/tif:DetailDescriptionComplementaire/tif:Description[@type='16.02.08']");
	
	$data->deschtml = $xml->xpath("/tif:OI/tif:DescriptionsComplementaires/tif:DetailDescriptionComplementaire[@type='16.01.10']/tif:Description");
		if (!(string)$data->deschtml[0]) $data->deschtml = $xml->xpath("/tif:OI/tif:DublinCore/dc:description");
	
	$data->desc = $xml->xpath("/tif:OI/tif:DescriptionsComplementaires/tif:DetailDescriptionComplementaire/tif:Description");
		if (!(string)$data->desc[0]) $data->deschtml = $xml->xpath("/tif:OI/tif:DublinCore/dc:description");
	
	$cat = $xml->xpath("/tif:OI/tif:DublinCore/tif:Classification");
	$scat = $xml->xpath("/tif:OI/tif:DublinCore/tif:ControlledVocabulary");
	/* AJOUT JFJ POUR DETAILS EQUIPEMENTS / CONFORT / SERVICES */
	$equipement = $xml->xpath("/tif:OI/tif:OffresPrestations/tif:DetailOffrePrestation[@type='15.05']/tif:DetailPrestation/tif:Prestation");
	$confort = $xml->xpath("/tif:OI/tif:OffresPrestations/tif:DetailOffrePrestation[@type='15.03']/tif:DetailPrestation/tif:Prestation");
	$service = $xml->xpath("/tif:OI/tif:OffresPrestations/tif:DetailOffrePrestation[@type='15.06']/tif:DetailPrestation/tif:Prestation");
	/* FIN AJOUT JFJ POUR DETAILS EQUIPEMENTS / CONFORT / SERVICES */
	$data->imgs = $xml->xpath("/tif:OI/tif:Multimedia/tif:DetailMultimedia[@type='03.01.01']/URL");
	$data->lat = $xml->xpath("/tif:OI/tif:Geolocalisations/tif:DetailGeolocalisation/tif:Zone/tif:Points/tif:DetailPoint/tif:Coordonnees/tif:DetailCoordonnees/tif:Latitude");
	$data->lon = $xml->xpath("/tif:OI/tif:Geolocalisations/tif:DetailGeolocalisation/tif:Zone/tif:Points/tif:DetailPoint/tif:Coordonnees/tif:DetailCoordonnees/tif:Longitude");
	$data->classement_type = $xml->xpath("/tif:OI/tif:Classements/tif:DetailClassement");
	$data->classement_val = $xml->xpath("/tif:OI/tif:Classements/tif:DetailClassement/tif:Classement");
	$data->begin_date = $xml->xpath("/tif:OI/tif:Periodes/tif:DetailPeriode/tif:Dates/tif:DetailDates/tif:DateDebut");
	$data->end_date = $xml->xpath("/tif:OI/tif:Periodes/tif:DetailPeriode/tif:Dates/tif:DetailDates/tif:DateFin");
	$data->onlinebooking = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.33']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.05']/tif:Coord");

	$data->tel = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.14']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.01']/tif:Coord");
		if (!(string)$data->tel[0]) {
			$data->tel = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.33']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.01']/tif:Coord");
			if (!(string)$data->tel[0]) $data->tel = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.01']/tif:Coord");
		}
	
	$data->fax = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.14']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.02']/tif:Coord");
	if (!(string)$data->fax[0]) $data->fax = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.33']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.02']/tif:Coord");
	
	$data->email = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.14']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.04']/tif:Coord");
	if (!(string)$data->email[0]) $data->email = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.33']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.04']/tif:Coord");
	if (!(string)$data->email[0]) $data->email = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.04']/tif:Coord");

	$data->url = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.14']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.05']/tif:Coord");
	if (!(string)$data->url[0]) $data->url = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.33']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.05']/tif:Coord");
	
	$data->fb = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.14']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.901']/tif:Coord");
	if (!(string)$data->fb[0]) $data->url = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.33']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.901']/tif:Coord");
	
	//$data->addr1 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.14']/tif:Adresses/tif:DetailAdresse/tif:Adr1");
	//if (!$data->addr1[0]) $data->addr1 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Adr1");

	$data->addr1 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.14']/tif:Adresses/tif:DetailAdresse/tif:Adr1");
	if (!(string)$data->addr1[0]) $data->addr1 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Adr1");
	
	$data->addr2 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.14']/tif:Adresses/tif:DetailAdresse/tif:Adr2");
	$data->addr3 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.14']/tif:Adresses/tif:DetailAdresse/tif:Adr3");
	
	$data->postcode = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.14']/tif:Adresses/tif:DetailAdresse/tif:CodePostal");
	//if (!$data->postcode[0]) $data->postcode = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:CodePostal");
	if (!(string)$data->postcode[0]) $data->postcode = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:CodePostal");
	
	
	$data->town = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.14']/tif:Adresses/tif:DetailAdresse/tif:Commune");
	//if (!$data->town[0]) $data->town = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Commune");
	if (!(string)$data->town[0]) $data->town = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Commune");
	

	//récupération infos pour manifestations
	$data->town_event = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Commune");
	$data->description_manif = $xml->xpath("/tif:OI/tif:DublinCore/dc:description");
	
	$data->lieu_manif_rs = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:RaisonSociale");
	$data->lieu_manif_addr1 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Adr1");
	$data->lieu_manif_addr2 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Adr2");
	$data->lieu_manif_addr3 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Adr3");
	$data->lieu_manif_cp = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:CodePostal");
	$data->lieu_manif_town = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Commune");
	
	$data->contact_manif_rs = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.32']/tif:RaisonSociale");
		//if (!$data->contact_manif_rs[0]) $data->contact_manif_rs = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:RaisonSociale");
	$data->contact_manif_addr1 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.32']/tif:Adresses/tif:DetailAdresse/tif:Adr1");
		//if (!$data->contact_manif_addr1[0]) $data->contact_manif_addr1 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Adr1");
	$data->contact_manif_addr2 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.32']/tif:Adresses/tif:DetailAdresse/tif:Adr2");
		//if (!$data->contact_manif_addr2[0]) $data->contact_manif_addr2 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Adr2");
	$data->contact_manif_addr3 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.32']/tif:Adresses/tif:DetailAdresse/tif:Adr3");
		//if (!$data->contact_manif_addr3[0]) $data->contact_manif_addr3 = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Adr3");
	$data->contact_manif_cp = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.32']/tif:Adresses/tif:DetailAdresse/tif:CodePostal");
		//if (!$data->contact_manif_cp[0]) $data->contact_manif_cp = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:CodePostal");
	$data->contact_manif_town = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.32']/tif:Adresses/tif:DetailAdresse/tif:Commune");
		//if (!$data->contact_manif_town[0]) $data->contact_manif_town = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Commune");
	$data->contact_manif_tel = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.32']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.01']/tif:Coord");
		//if (!$data->contact_manif_tel[0]) $data->contact_manif_tel = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.13']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.01']/tif:Coord");
	
	$data->contact_manif_port = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.32']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.900']/tif:Coord");
	
	$data->contact_manif_siteweb = $xml->xpath("/tif:OI/tif:Contacts/tif:DetailContact[@type='04.03.32']/tif:Adresses/tif:DetailAdresse/tif:Personnes/tif:DetailPersonne/tif:MoyensCommunications/tif:DetailMoyenCom[@type='04.02.05']/tif:Coord");
		

	if ((string)$data->lieu_manif_rs[0]) {
		$lines = array();
		$lines[] = (string)$data->lieu_manif_rs[0];
		if ($data->lieu_manif_addr1[0]) $lines[] = (string)$data->lieu_manif_addr1[0];
		if ($data->lieu_manif_addr2[0]) $lines[] = (string)$data->lieu_manif_addr2[0];
		if ($data->lieu_manif_addr3[0]) $lines[] = (string)$data->lieu_manif_addr3[0];
		$lines[] = (string)$data->lieu_manif_cp[0] . " " . (string)$data->lieu_manif_town[0];
		$data->lieu_manif = implode("\n", $lines);
	}

	if ((string)$data->contact_manif_rs[0]) {
		$lines = array();
		$lines[] = (string)$data->contact_manif_rs[0];
		if ($data->contact_manif_addr1[0]) $lines[] = (string)$data->contact_manif_addr1[0];
		if ($data->contact_manif_addr2[0]) $lines[] = (string)$data->contact_manif_addr2[0];
		if ($data->contact_manif_addr3[0]) $lines[] = (string)$data->contact_manif_addr3[0];
		$lines[] = (string)$data->contact_manif_cp[0] . " " . (string)$data->contact_manif_town[0];
		if ($data->contact_manif_tel[0]) $lines[] = (string)$data->contact_manif_tel[0];
		$data->contact_manif = implode("\n", $lines);
	}

	$dayslist = array(
		'Lundi' => 1,
		'Mardi' => 2,
		'Mercredi' => 3,
		'Jeudi' => 4,
		'Vendredi' => 5,
		'Samedi' => 6,
		'Dimanche' => 7,
	);
	$idayslist = array(
		1 => 'Lundi',
		2 => 'Mardi',
		3 => 'Mercredi',
		4 => 'Jeudi',
		5 => 'Vendredi',
		6 => 'Samedi',
		7 => 'Dimanche',
	);

	$horaires = $xml->xpath("/tif:OI/tif:Periodes/tif:DetailPeriode[@type='09.01.06']");
	$ouvertures = array();;
	foreach ($horaires as $periode) {
		$pd = new stdClass;
		$t = $periode->xpath("tif:Dates/tif:DetailDates/tif:DateDebut");
		$pd->begin = (string)$t[0];
		$t = $periode->xpath("tif:Dates/tif:DetailDates/tif:DateFin");
		$pd->finish = (string)$t[0];

		$pb = (object)date_parse($pd->begin);
		$pf = (object)date_parse($pd->finish);
		if ($pb && $pf) {
			setlocale(LC_TIME, 'fr_FR');
			$pb->month = $month_name = strftime('%B', mktime(0, 0, 0, $pb->month));
			$pf->month = $month_name = strftime('%B', mktime(0, 0, 0, $pf->month));

			if ($pb->year == $pf->year)
				$pd->all_date = "Du {$pb->day} {$pb->month} {$pb->year} au {$pf->day} {$pf->month}";
			else
				$pd->all_date = "Du {$pb->day} {$pb->month} {$pb->year} au {$pf->day} {$pf->month} {$pf->year}";
		}

		if (strtotime($pd->finish) < time()) continue;

		$days = $periode->xpath("tif:Dates/tif:DetailDates/tif:Jours/tif:DetailJours/tif:Jour");
		$pd->days = array();
		foreach ($days as $day) {
			$d = new stdClass;
			$n = (string)$day->attributes()->{'libelle'}[0];
			if ($dayslist[$n]) {
				$pd->days[$dayslist[$n]] = $d;
				$t = $day->xpath("tif:Horaires/tif:HoraireDebut");
				$d->begin = (string)$t[0];
				$t = $day->xpath("tif:Horaires/tif:HoraireFin");
				$d->finish = (string)$t[0];
			}
		}
		ksort($pd->days);

		if ($pd->all_date)
			$txtperiod = "<div class='open_period'><p>{$pd->all_date} :</p><ul>";
		else
			$txtperiod = "<div class='open_period'><p>Du {$pd->begin} au {$pd->finish} :</p><ul>";
		foreach ($pd->days as $k => $d) {
			$txtperiod .= "<li><strong>".$idayslist[$k]."</strong> : {$d->begin} &agrave {$d->finish}</li>";
		}
		$txtperiod .= "</ul></div>";
		$ouvertures[] = $txtperiod;
	}
	$data->ouverture = implode("\n", $ouvertures);


	$traifslist = $xml->xpath("/tif:OI/tif:Tarifs/tif:DetailTarifs/tif:DetailTarif");
	$tarifs = array();
	foreach ($traifslist as $xtarif) {
		$pd = new stdClass;
		$pd->begin = xpathstring($xtarif, "tif:DetailPeriode/tif:Dates/tif:DetailDates/tif:DateDebut");
		$pd->finish = xpathstring($xtarif, "tif:DetailPeriode/tif:Dates/tif:DetailDates/tif:DateFin");

		$pb = (object)date_parse($pd->begin);
		$pf = (object)date_parse($pd->finish);
		if ($pb && $pf) {
			setlocale(LC_TIME, 'fr_FR');
			$pb->month = $month_name = strftime('%B', mktime(0, 0, 0, $pb->month));
			$pf->month = $month_name = strftime('%B', mktime(0, 0, 0, $pf->month));

			if ($pb->year == $pf->year)
				$pd->all_date = "Du {$pb->day} {$pb->month} {$pb->year} au {$pf->day} {$pf->month}";
			else
				$pd->all_date = "Du {$pb->day} {$pb->month} {$pb->year} au {$pf->day} {$pf->month} {$pf->year}";
		}

		if ($pd->finish && strtotime($pd->finish) < time()) continue;

		$n = (string)$xtarif->attributes()->{'libelle'}[0];
		$nn = $xtarif->xpath("tif:Nom");
		if ((string)$nn[0]) $n = (string)$nn[0];
		$pd->name = $n;

		$pd->tarif = xpathstring($xtarif, "tif:TarifStandard");
		if (!$pd->tarif || $pd->tarif == "00.0") {
			$m = xpathstring($xtarif, "tif:TarifMin");
			$M = xpathstring($xtarif, "tif:TarifMax");
			if ($m && $M) {
				if ($m == $M)
					$pd->tarif = "$M";
				else
					$pd->tarif = "$m &agrave $M";
			}
		}
		$pd->tarif .= " ".xpathstring($xtarif, "tif:TarifDevise");
		$pd->desc = xpathstring($xtarif, "tif:DescriptionTarif");

		if ($pd->all_date)
			$txtperiod = "<span class='date'>{$pd->all_date}</span>";
		else
			$txtperiod = "<span class='date'>Du {$pd->begin} au {$pd->finish}</span>";

		//print_r($pd);
		$txttarif = "<ul class='tarif'>
		<li>
		<span class='name'><strong>{$pd->name}</strong></span><br />
		<span class='price'>{$pd->tarif}</span><br />
		{$txtperiod}<br />
		<span class='desc'>{$pd->desc}</span><br />
		</li>
		</ul>";
		$tarifs[] = $txttarif;
	}
	$data->tarif = implode("\n", $tarifs);

	if ($data->begin_date) $data->begin_date = (string)$data->begin_date[0];
	if ($data->end_date) $data->end_date = (string)$data->end_date[0];

	$data->cat = array();
	foreach($cat as $c) $data->cat[(string)$c->attributes()->code] = (string)$c;

	$data->scat = array();
	foreach($scat as $c) $data->scat[(string)$c->attributes()->code] = (string)$c;
	
	/* AJOUT JFJ POUR DETAILS EQUIPEMENTS / CONFORT / SERVICES */
	$data->equipement = array();
	foreach($equipement as $c) $data->equipement[(string)$c->attributes()->type] = (string)$c;
	
	$data->confort = array();
	foreach($confort as $c) $data->confort[(string)$c->attributes()->type] = (string)$c;
	
	$data->service = array();
	foreach($service as $c) $data->service[(string)$c->attributes()->type] = (string)$c;
	/* FIN AJOUT JFJ POUR DETAILS EQUIPEMENTS / CONFORT / SERVICES */
	
	if ($data->classement_type && $data->classement_type[0]) {
		$data->ctype = array(
			id => (string)$data->classement_type[0]->attributes()->type,
			name => (string)$data->classement_type[0]->attributes()->libelle
			);
	}

	if ($data->classement_val && $data->classement_val[0]) {
		$data->cval = (string)$data->classement_val[0];
	}

	if ($data->deschtml[0]) $data->desc = $data->deschtml;
	if ($data->deschtml2[0]) $data->desc = $data->deschtml2;

	//if ((string)$data->id[0] == "32.TG.157.018503.10") {
	$datas[(string)$data->id[0]] = $data;
	// foreach ((array)$data as $k => $v) {
	// 	print("  == ".((string)$k)." :: ".((string)$v[0])."\n");
	// };
	//print_r($data);
	//}
}

function parse_xmls() {
	if ($handle = opendir('import/tmp/xmls/')) {
		while (false !== ($f = readdir($handle))) {
			if (preg_match("/xml$/", $f)) {
				echo " * Parsing $f\n";
				parse_xml("import/tmp/xmls/$f");
			}
		}
		closedir($handle);
	}
	if ($handle = opendir('import/add-xmls/')) {
		while (false !== ($f = readdir($handle))) {
			if (preg_match("/xml$/", $f)) {
				echo " * Parsing AddonXML $f\n";
				parse_xml("import/add-xmls/$f");
			}
		}
		closedir($handle);
	}
}

function import_nodes() {
	global $datas;

	// 0 pour importer un seul node en relation avec la ligne 83
	// 1 pour tout importer
	if (1) {
		$res = db_query("select entity_id from field_data_field_xmlid where entity_type='node'");
		while ($row = $res->fetchObject()) {
			try {
				print("Unpublish node {$row->entity_id}\n");
				$node = node_load($row->entity_id);
				$node->status = 0;
				$node = node_submit($node);
				node_save($node);
			}
			catch (PDOException $e) {}
		}
	}

	print("=== Start\n");

	foreach ($datas as $xmlid => $d) {
		$nkind = "tif";
		if ($nkind) {
			$res = db_query("select entity_id from field_data_field_xmlid where entity_type='node' and field_xmlid_value='{$xmlid}'");
			if ($res) $row = $res->fetchObject();
			if (!$row || !$row->entity_id) { $node = new stdClass; $node->nid = NULL; }
			else {
				$node = node_load($row->entity_id);
				print("Loading node to update {$row->entity_id}\n");
			}

			foreach ($d->imgs as $i => $img) {
				$dname = preg_replace("/[^a-zA-Z0-9-.]/", "_", (string)$img);
				print(" Importing image = $img == $dname\n");
				system("wget -q -O import/tmp/imgs/$dname '".$img."'");

				$ires = db_query("select fid from file_managed where filename='{$dname}'");
				if ($ires) $irow = $ires->fetchObject();
				if (!$irow || !$irow->fid) {
					$drupal_file = file_save_data(file_get_contents("import/tmp/imgs/$dname"), "public://$dname");
					print("  * New image\n");
				} else {
					$drupal_file = file_load($irow->fid);
					copy("import/tmp/imgs/$dname", "sites/default/files/$dname");
					print("  * Updating image\n");
				}
				if ($i == 0) $node->field_photo_principale[$node->language][0] = get_object_vars($drupal_file);
				else if ($i <= 5) $node->field_photo_secondaire_1[$node->language][$i-1] = get_object_vars($drupal_file);
			}

			$node->type = $nkind;
			$node->language = LANGUAGE_NONE;
			$node->title = (string)$d->name[0];
			$node->status = 1;
			$node->body[$node->language][0]['value'] = html_entity_decode((string)$d->desc[0], ENT_COMPAT | ENT_HTML401, "UTF-8");
			$node->field_xmlid[$node->language][0]['value'] = $xmlid;
			

			if ((string)$d->addr1[0]||(string)$d->addr2[0]||(string)$d->addr3[0]) $node->field_adresse_1[$node->language][0]['value'] = (string)$d->addr1[0] . "\n" . (string)$d->addr2[0] . "\n" . (string)$d->addr3[0];
			if ((string)$d->postcode[0]) $node->field_code_postal[$node->language][0]['value'] = (string)$d->postcode[0];
			if ((string)$d->town[0]) $node->field_ville[$node->language][0]['value'] = (string)$d->town[0];
			
			if((string)$d->deschtml3[0]) $node->field_deschtml2[$node->language][0]['value'] = (string)$d->deschtml3[0];

			if((string)$d->cartegmap[0]) $node->field_cartegmap[$node->language][0]['value'] = (string)$d->cartegmap[0];


			if ((string)$d->town_event[0]) $node->field_ville_manif[$node->language][0]['value'] = (string)$d->town_event[0];
			if ((string)$d->lat[0]) $node->field_latitude[$node->language][0]['value'] = (string)$d->lat[0];
			if ((string)$d->lon[0]) $node->field_longitude[$node->language][0]['value'] = (string)$d->lon[0];
			if ((string)$d->tel[0]) $node->field_t_l_phone[$node->language][0]['value'] = (string)$d->tel[0];
			if ((string)$d->fax[0]) $node->field_fax[$node->language][0]['value'] = (string)$d->fax[0];
			if ((string)$d->email[0]) $node->field_email[$node->language][0]['email'] = (string)$d->email[0];
			if ((string)$d->url[0]) $node->field_url[$node->language][0]['value'] = (string)$d->url[0];
			if ((string)$d->fb[0]) $node->field_facebook[$node->language][0]['value'] = (string)$d->fb[0];
			if ((string)$d->onlinebooking[0]) $node->field_onlinebooking[$node->language][0]['value'] = (string)$d->onlinebooking[0];
			if ((string)$d->description_manif[0]) $node->field_description_manif[$node->language][0]['value'] = (string)$d->description_manif[0];
			if ($d->lieu_manif) $node->field_lieu_manif[$node->language][0]['value'] = $d->lieu_manif;
			if ($d->contact_manif) $node->field_contact_manif[$node->language][0]['value'] = $d->contact_manif;
			if ($d->horaire_manif) $node->field_horaires[$node->language][0]['value'] = $d->horaire_manif;

			// import manif info
			if ((string)$d->contact_manif_rs) $node->field_manif_info_raisonsociale[$node->language][0]['value'] = (string)$d->contact_manif_rs[0];
			if ((string)$d->contact_manif_siteweb) $node->field_manif_info_siteweb[$node->language][0]['value'] = (string)$d->contact_manif_siteweb[0];
			if ((string)$d->contact_manif_port) $node->field_manif_info_portable[$node->language][0]['value'] = (string)$d->contact_manif_port[0];
			if ((string)$d->contact_manif_tel) $node->field_manif_info_tel[$node->language][0]['value'] = (string)$d->contact_manif_tel[0];
			if ((string)$d->contact_manif_addr1) $node->field_manif_info_adr1[$node->language][0]['value'] = (string)$d->contact_manif_addr1[0];
			if ((string)$d->contact_manif_addr2) $node->field_manif_info_adr2[$node->language][0]['value'] = (string)$d->contact_manif_addr2[0];
			if ((string)$d->contact_manif_cp) $node->field_manif_info_codepostal[$node->language][0]['value'] = (string)$d->contact_manif_cp[0];
			if ((string)$d->contact_manif_town) $node->field_manif_info_commune[$node->language][0]['value'] = (string)$d->contact_manif_town[0];

			// import manif lieu
			if ((string)$d->lieu_manif_rs) $node->field_manif_lieu_raisonsociale[$node->language][0]['value'] = (string)$d->lieu_manif_rs[0];
			if ((string)$d->lieu_manif_addr1) $node->field_manif_lieu_adr1[$node->language][0]['value'] = (string)$d->lieu_manif_addr1[0];
			if ((string)$d->lieu_manif_addr2) $node->field_manif_lieu_adr2[$node->language][0]['value'] = (string)$d->lieu_manif_addr2[0];
			if ((string)$d->lieu_manif_addr3) $node->field_manif_lieu_adr3[$node->language][0]['value'] = (string)$d->lieu_manif_addr3[0];
			if ((string)$d->lieu_manif_cp) $node->field_manif_lieu_codepostal[$node->language][0]['value'] = (string)$d->lieu_manif_cp[0];
			if ((string)$d->lieu_manif_town) $node->field_manif_lieu_commune[$node->language][0]['value'] = (string)$d->lieu_manif_town[0];


			// import manif lieu
			
			if ($d->ouverture) $node->field_ouverture[$node->language][0]['value'] = $d->ouverture;
			if ($d->tarif) $node->field_tarifs[$node->language][0]['value'] = $d->tarif;

			$node->field_categorie[$node->language] = array();
			$node->field_idcat[$node->language] = array();
			$i = 0; foreach($d->cat as $k => $v) {
				$node->field_categorie[$node->language][$i]['value'] = (string)$v;
				$node->field_idcat[$node->language][$i++]['value'] = (string)$k;
			}
			$node->field_sous_categorie[$node->language] = array();
			$node->field_idsubcat[$node->language] = array();
			$i = 0; foreach($d->scat as $k => $v) {
				$node->field_sous_categorie[$node->language][$i]['value'] = (string)$v;
				$node->field_idsubcat[$node->language][$i++]['value'] = (string)$k;
			}
			/* AJOUT JFJ POUR DETAILS EQUIPEMENTS / CONFORT / SERVICES */
			$node->field_equipements[$node->language] = array();
			$node->field_idequipements[$node->language] = array();
			$i = 0; foreach($d->equipement as $k => $v) {
				$node->field_equipements[$node->language][$i]['value'] = (string)$v;
				$node->field_idequipements[$node->language][$i++]['value'] = (string)$k;
			}
			$node->field_confort[$node->language] = array();
			$node->field_idconfort[$node->language] = array();
			$i = 0; foreach($d->confort as $k => $v) {
				$node->field_confort[$node->language][$i]['value'] = (string)$v;
				$node->field_idconfort[$node->language][$i++]['value'] = (string)$k;
			}
			$node->field_services[$node->language] = array();
			$node->field_idservices[$node->language] = array();
			$i = 0; foreach($d->service as $k => $v) {
				$node->field_services[$node->language][$i]['value'] = (string)$v;
				$node->field_idservices[$node->language][$i++]['value'] = (string)$k;
			}
			/* FIN AJOUT JFJ POUR DETAILS EQUIPEMENTS / CONFORT / SERVICES */
			
			if ($d->cval) $node->field_classement_value[$node->language][0]['value'] = (string)$d->cval;
			if ($d->ctype) {
				$node->field_classement_type[$node->language][0]['value'] = (string)$d->ctype['id'];
				$node->field_classement_label[$node->language][0]['value'] = (string)$d->ctype['name'];
			}

			if ($d->begin_date) $node->field_date_start[$node->language][0]['value'] = (string)$d->begin_date;
			if ($d->end_date) $node->field_date_end[$node->language][0]['value'] = (string)$d->end_date;
			$node = node_submit($node);
//			print_r($node);
			// ligne à commenter pour importer un seul node
			node_save($node);

		}
	}
}

get_files();
parse_xmls();
import_nodes();

$user = $original_user;
?>