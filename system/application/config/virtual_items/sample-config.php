<?php 
$vi['title_id'] = 12345;
$vi['feed'] = array(
  'https://oai.website.net/?verb=ListRecords&metadataPrefix=oai_dc&set=something',
);
$vi['feed_type'] = 'oai';
$vi['oai_from'] = '2010-01-01';
$vi['copyright'] = 1; // In copyright, permission granted
$vi['creative_commons'] = 'http://creativecommons.org/licenses/by/4.0';

// Name of function to return an array containing: 
// virtual-id, virtual-volume, volume, series, issue, page-start, page-end, date, year
$vi['vi_identifier_data'] = function ($oai_record) {
  $ret = array(
    'virtual-id' => null, 
    'virtual-volume' => null,
    'volume' => null, 
    'series' => null, 
    'issue' => null, 
    'page_start' => null,
    'page_end' => null,
    'date' => null,
    'year' => null
  );

  $source = (string)$oai_record->xpath('//dc:source')[0];
  $id = (string)$oai_record->xpath('//header/identifier')[0];
  $dt = date_create((string)$oai_record->xpath('//header/datestamp')[0]);
  $ret['date'] = date_format($dt, 'd M Y');
  $ret['year'] = date_format($dt, 'Y');
  $matches = [];
  if (preg_match("/^(.*?) (\d+)\((\d+)\): (\d+)-(\d+)/", $source, $matches)) {
    $ret['volume'] = $matches[2];
    $ret['issue'] = $matches[3];
    $ret['page-start'] = $matches[4];
    $ret['page-end'] = $matches[5];
  }

  $ret['virtual-volume'] = "v.".$ret['volume'].':no.'.$ret['issue'].' ('.$ret['date'].')';

  $x = explode('.', $id);
  $y = array_pop($x);
  $ret['virtual-id'] = implode('.',$x);
  return $ret;
};

$vi['get_pdf'] = function($oai_record, $config) {
  $source = '';
  foreach ($oai_record->xpath('//dc:identifier') as $i) {
    if (preg_match('/\/article\//i', (string)$i)) {
      $source = (string)$i; 
    }
  }
  // Our source of https://dez.pensoft.net/article/96986/
  // becomes https://dez.pensoft.net/article/96986/download/pdf/
  // to get the PDF
  if ($source) {
    $path = $config['working_path'].'/'.preg_replace("/[^A-Za-z0-9]+/", '_', $source).'.pdf';
    if (!file_exists($path)) {
      $ch = curl_init($source.'download/pdf');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		
      curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
      $data = curl_exec($ch);		
      curl_close($ch);
      
      $result = file_put_contents($path, $data);
    }	
    return $path;
  }

  return null;
};
