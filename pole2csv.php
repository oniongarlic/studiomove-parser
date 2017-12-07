#!/usr/bin/php -q
<?php

if ($argc<2)
  die("Usage: pole2csv.php reservationhistory.txt\n");

$r=file($argv[1]);
if (!$r)
  die("Invalid input file\n");

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

for ($i=0;$i<=$c;$i++) {
 if (($i % 7==0) && ($i>0)) {
//  print_r($a);
  fputcsv($fp, $a);

  // Parse "Class name - Who"
  $sep=strrpos($a[3], '-');
  $cl=substr($a[3], 0, $sep-1);
  $wl=substr($a[3], $sep+2);
  $a['who']=trim($wl);
  $a['class']=trim($cl);

  $s = $db->prepare("INSERT INTO history (class, who, date, time, place) VALUES (:c, :w, :d, :t, :p)");
  $s->bindParam(':c', $cl);
  $s->bindParam(':w', $wl);
  $s->bindParam(':d', $a[4]);
  $s->bindParam(':t', $a[5]);
  $s->bindParam(':p', $a[6]);
  $s->execute();

  $t[]=$a;

  $a=array();
 }
 if ($i==$c)
  break;
 $ri=$i % 7;
 $a[$ri]=trim($r[$i]);
}

fclose($fp);

$tmp=json_encode($t);
file_put_contents('pole.json', $tmp);
