Exiftool
========

Program na dávkovou editaci EXIF u obrázků [jpg, jpeg].


	Vytvoříte si csv soubor oddělený středníkem, v kterém jsou potřebné 2 sloupce: name;date
	[sloupců můžete mít ve zdrojovém souboru kolik chcete, pořadí sloupců není nijak určené].
	name = název souboru.jpg, date = datum na který chcete nastavit datum vytvoření,
	datum poslední změny a datum EXIF.

Více informací o exiftool:
--------------------------
  1) http://freeweb.siol.net/hrastni3/foto/exif/exiftoolgui.htm

  2) http://www.sno.phy.queensu.ca/~phil/exiftool/faq.html


Příklad:
--------
  1) obrázky si uložíme do adresáře root_webu/images/...

  2) importační soubor uložíme do root_webu

  3) php soubor:

	<?
	include ('exiftool_class.php');

	$exiftool = new Exiftool;
	$exiftool->setBasePath('/www/exiftool/images/');
	$exiftool->setFile(__DIR__ . '/import.csv');
	// vypnutí přepisování originálu obrázku
	//$exiftool->setReplace(false);
	$result = $exiftool->import();
	if ($result) {
		echo "<h1>Exiftool proběhl úspěšně.</h1>";
		echo $exiftool->getMessageDone();
		if ($exiftool->getMessageError() != '') {
				echo "<br>Výpis chyb:<br />";
				echo $exiftool->getMessageError();
		}
	} else {
		echo "<h1>Exiftool proběhl neúspěšně.</h1>";
		echo $exiftool->getMessageError();
	}
	?>

  4) v root_webu je uložený exe soubor: exiftool.exe

