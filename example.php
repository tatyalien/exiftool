<?php

include ('exiftool_class.php');

$exiftool = new Exiftool;
$exiftool->setBasePath('/www/_rozdelano/exiftool/images/');
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
