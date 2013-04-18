<?php

/**
 * Třída editující exif u obrázků jpg, jpeg.
 * Doba běhu skriptu se může protáhnout u více souborů, pracuje se externě s programem exiftool.exe na Windows.
 * Nastavení:
 * @param setFile - zdrojový csv soubor.
 * @param setBasePath - cesta k obrázkům
 * @param setReplace - přepisování originálního obrázku
 * @param setRowStart - start čtení dat na řádku X csv souboru
 * 
 * Spuštění akce:
 * @param import()
 */
class Exiftool
{
    /** @var File */
    private $file;
    /** @var BasePath */
    private $basePath = '/';
    /** @var Replace */
    private $replace = true;
    /** @var startovní pozice čtení csv souboru */
    private $rowStart = 2;
    /** @var Message error */
    private $messageError = '';
    /** @var Message done */
    private $messageDone = '';

    /**
     * @return string $this->messageDone
     */
    public function getMessageDone()
    {
        return $this->messageDone;
    }

    /**
     * @return string $this->messageError
     */
    public function getMessageError()
    {
        return $this->messageError;
    }

    /**
     * @param string $file
     * @return void
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @param string $basePath [default value = '/']
     * @return void
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @param boolean $replace [default value = 'true']
     * @return void
     */
    public function setReplace($replace)
    {
        $this->replace = (boolean) $replace;
    }

    /**
     * @param int $rowStart [default value = '2']
     * @return void
     */
    public function setRowStart($rowStart)
    {
        $this->rowStart = (int) $rowStart;
    }
    
    /**
     * Spuštění akce na editaci exiftool.
     * Výsledné informace se po akci dají získat:
     * getMessageError()
     * getMessageDone()
     * 
     * @return boolean
     */
    public function import()
    {
        // kontrola, zda existuje zdrojový soubor
        if (!File_Exists($this->file)){
            $this->messageError = 'Soubor "'.$this->file.'" neexistuje.';
            return false;
        }
        
        // defaultní hodnoty
        $i = 1;
        $i_images = 0;
        $error = '';
        $i_name = NULL;
        $i_date = NULL;
        $name = '';
        $date = '';
        $dateNew = NULL;
        
        // pokuď je replace vyplé, zachová se originální soubor a upraví se kopie [originál se přejmenuje na name.jpg_original]   
        $replace = ($this->replace) ? '-overwrite_original' : '';
        
        // načtení zdrojového csv souboru
        $handle = fopen($this->file, "r");
        while (($data = fgetcsv($handle, 32000, ";")) !== false) {
            if ($i == 1) {
                // projdu teďkont v prvním řádku pole a zjistím kde se nachází všechny potřebné hodnoty - sloupce
                $a = 0;
                foreach ($data as & $val) {
                    // zde kontroluji názvy sloupců, které potřebuji pro funkci
                    if (iconv('windows-1250', 'utf-8', trim($val)) == "name") {
                        $i_name = $a;
                    }
                    if (iconv('windows-1250', 'utf-8', trim($val)) == "date") {
                        $i_date = $a;
                    }
                    $a++;
                }
                // odstraním dočasnou proměnnou
                unset($a);
                // pokuď není nastavena potřebná hodnota, ukončím funkci
                if (is_null($i_name) or is_null($i_date)) {
                    $error .= "Vytvořeno: " . date("d.m.y G:i:s") . "<br />";
                    $error .= "Ve zdrojovém souboru nejsou správně data, data musí obsahovat sloupce (oddělené středníkem - csv):<br />";
                    $error .= "name<br />";
                    $error .= "date<br />";
                    $this->messageError = $error;
                    // ukončení skriptu
                    return false;
                }
            }
            if ($i >= $this->rowStart) {
                // rozsekám data do pole - řádek a opravím si kódování
                $name = iconv('windows-1250', 'utf-8', trim($data[$i_name]));
                $date = iconv('windows-1250', 'utf-8', trim($data[$i_date]));
                
                // kontrola existence souboru
                if(!$this->fileExist($name, $this->basePath)) {
                    $error .= 'Řádek '.$i.' => name "'.$_SERVER['DOCUMENT_ROOT'].$this->basePath.$name.'" neexistuje.<br />';
                    $i++;
                    $i_images++;
                    $name = '';
                    $date = '';
                    continue;
                }
                
                // kontrola zadaného data + převedení na formát [rrrr:mm:dd hh:mm:ss]
                $dateNew = $this->kontrolaData($date); 
                if($dateNew === false) {
                    $error .= 'Řádek '.$i.' => date "'.$date.'" neexistující datum.<br />';
                    $i++;
                    $i_images++;
                    $name = '';
                    $date = '';
                    continue;
                }
                
                // dodání cesty k obrázku
                $imageName = $_SERVER['DOCUMENT_ROOT'].$this->basePath.$name;
                eval(`"exiftool(-k).exe" -php -q -AllDates="$dateNew" -filemodifydate="$dateNew" -filecreatedate="$dateNew" $replace $imageName`);
                $dateNew = NULL;
                $i_images++;
            }
            
            $i++;
        }
        $this->messageError = $error;
        $this->messageDone = "Hotovo.<br />Zpracováno obrázků: $i_images";
        return true; 
    }

    /** Kontrola data
     * @param string $date ve formátu: [rrrr:mm:dd hh:mm:ss], nebo [dd.mm.rrrr hh:mm:ss]
     * 
     * @return boolean
     */
    private function kontrolaData($date) {
        // odstranění více mezer [pokuď uživatel zadal více mezi datumem a časem]
        while (!(strpos($date, '  ') === false)) {
            $date = str_replace('  ', ' ', $date);
        }
        
        $poleDate = explode(' ', $date);
        // pokuď není zadaný datum a čas
        if(count($poleDate) != 2) {
            return false;
        }
        
        // kontrola pokuď je datum oddělený ":"
        if (!(strpos($poleDate[0], ":") === false)) {
            $poleOnlyDate = explode(':', $poleDate[0]);
            // odstranění úvodní nuly
            if(substr($poleOnlyDate[2], 0, 1) == "0") {
                $poleOnlyDate[2] = substr($poleOnlyDate[2],1);    
            }
            if(substr($poleOnlyDate[1], 0, 1) == "0") {
                $poleOnlyDate[1] = substr($poleOnlyDate[1],1);    
            }
            // kontrola správnosti data
            if(!$this->platne_datum(trim($poleOnlyDate[2]).'.'.trim($poleOnlyDate[1]).'.'.trim($poleOnlyDate[0]))) {
                return false;
            }
            // pokuď datum je v platném rozsahu vracím zpět neupravný datum [je ve správném formátu]
            return $date;
        } else {
            if(!$this->platne_datum($poleDate[0])) {
                return false;    
            }
            $poleOnlyDate = explode('.', $poleDate[0]);
            return trim($poleOnlyDate[2]).':'.trim($poleOnlyDate[1]).':'.trim($poleOnlyDate[0]).' '.trim($poleDate[1]);
        }
    }
    
    /** Kontrola data
     * @param string datum ve formátu d.m.rrrr
     * 
     * @return bool platnost data
     * @copyright Jakub Vrána, http://php.vrana.cz/
     */
    private function platne_datum($datum) {
        return preg_match('~^([1-9]|19|[12][0-8]|29(?=\\.([^2]|2\\.(([02468][048]|[13579][26])00|[0-9]{2}(0[48]|[2468][048]|[13579][26]))))|30(?=\\.[^2])|31(?=\\.([13578][02]?\\.)))\\.([1-9]|1[012])\\.[0-9]{4}$~D', $datum);
    }
    
    /**
     * Kontrola existence souboru
     * @param string $file
     * @param string $basePath = '/'
     * 
     * @return boolean
     */
    private function fileExist($file, $basePath='/') {
        $isExisting = @fopen($_SERVER['DOCUMENT_ROOT'].$basePath.$file,"r");
        return $isExisting ? true : false;  
    }
}
