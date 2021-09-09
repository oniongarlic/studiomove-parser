#!/usr/bin/php -q
<?php

if ($argc<2)
  die("Usage: pole2csv.php reservationhistory.txt\n");

$r=file($argv[1]);
if (!$r)
  die("Invalid input file\n");

// Months
$ms=array('tammikuu','helmikuu','maaliskuu','huhtikuu','toukokuu','kesäkuu','heinäkuu','elokuu','syyskuu','lokakuu','marraskuu','joulukuu');

$c=count($r);
$a=array();
$t=array();
$db=new PDO('sqlite:pole.db');

// Create tables
$db->exec("CREATE TABLE IF NOT EXISTS history (class text,date varchar, time varchar, place text, who text);");
$db->exec("CREATE UNIQUE INDEX IF NOT EXISTS history_index on history (class,date,time);");

$fp=fopen('polehistory.csv', 'w');
if (!$fp)
  die("Failed to open output file\n");

fputcsv($fp, array('Month','Day','WD','Class','Date','Time','Where'));

// Remove old
$db->exec("DELETE FROM history");

$vd=false;
$ri=0;
$canc=0;
$drop=0;
$total=0;

$db->beginTransaction();

for ($i=0;$i<=$c;$i++) {
 if ($vd) {

  // Sanity check fields
  if (!in_array($a[1], $ms)) {
   die('Parse error, invalid month');
  }
  if ((int)$a[2]<1 && (int)$a>31) {
   die('Parse error, invalid day');
  }

  $valid=true;

  if (strpos($a[4], 'Peruttu')!==false) {
   $canc++;
   $valid=false;
  }

  if (isset($a[8]) && strpos($a[8], 'Odotuslistalla')!==false) {
   $drop++;
   $valid=false;
  }

  if ($valid) {
   $total++;

  // Parse "Class name - Who"
   $sep=strrpos($a[4], '-');
   if ($sep>0) {
    $cl=substr($a[4], 0, $sep-1);
    $wl=substr($a[4], $sep+2);
    $a['who']=trim($wl);
    $a['class']=trim($cl);
   } else {
    // Not specified
    $a['who']='';
    $a['class']=$a[4];
   }

   print_r($a);

   $s=$db->prepare("INSERT INTO history (class, who, date, time, place) VALUES (:c, :w, :d, :t, :p)");
   $s->bindParam(':c', $cl);
   $s->bindParam(':w', $wl);
   $s->bindParam(':d', $a[4]);
   $s->bindParam(':t', $a[5]);
   $s->bindParam(':p', $a[6]);
   $s->execute();

   fputcsv($fp, $a);

   $t[]=$a;
  }

  $a=array();
  $vd=false;
  $ri=0;
 }

 if ($i==$c)
  break;

 // Get the row, trim it
 $rd=trim($r[$i]);

 // Check if it is a month, and if so we are on the next reservation already!
 if (!$vd && $ri>0 && in_array($rd, $ms)) {
   $vd=true;
   $i--;
   continue;
 } else {
   $ri++;
   $a[$ri]=$rd;
 }

}

$db->commit();

fclose($fp);

printf("Total: %d\nCancelled: %d\nDropped: %d\n", $total, $canc, $drop);

$tmp=json_encode($t);
file_put_contents('pole.json', $tmp);
