<?php
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Connection: close");
/** Google Drive GET PUBLIC FOLDER **/
$folderId = '[INSERT ID FOLDER GOOGLE DRIVE]';
$url = "https://drive.google.com/drive/folders/{$folderId}";

$output = [];

try {

    $html = @file_get_contents($url,true);
    if($html==false){
       throw new Exception( 'Something really gone wrong');  
    }

    preg_match_all('/data-id="([^"]*)"|data-tooltip="([^"]*)"/i', $html, $matches);
    $data = isset($matches[0]) ? array_slice($matches[0], 3) : [];

    $datos = array();
    $currentDataId = '';

    if(is_array($data) && count($data) > 0){

        if(str_contains(implode('',$data),'data-id=') !== true){
            throw new Exception('data-id Missed');  
        }

        foreach ($data as $value) {
            if (strpos($value, 'data-id=') !== false) {
                preg_match('/data-id="([^"]+)"/', $value, $id_file);
                $currentDataId = isset($id_file[1]) ? strip_tags($id_file[1]) : '';
                $datos[$currentDataId] = array();
            } else {
                if (!empty($currentDataId)) {
                    preg_match('/data-tooltip="([^"]+)"/', $value, $mat2);
                    $val = isset($mat2[1]) ? $mat2[1] : '';
                    $val = str_ireplace(['Comma Separated Values:','Shared','Owner hidden','Last modified by','Size:'], '',$val);
                    $val = trim($val);
                    if($val !== ''):
                    $nval = is_numeric(strtotime($val)) ? date("Y-m-d", strtotime($val)) : $val;
                    
                    $datos[$currentDataId][] = $nval;
                    
                    $file_id = $currentDataId;
                    $formato_file = pathinfo($datos[$currentDataId][0], PATHINFO_EXTENSION);
                    $cadena = array(
                        'file' => $file_id, 
                        'formato' => $formato_file,                        
                    );
                    
                    if(in_array($formato_file,['xls','xlsx'])){
                        $cadena['url_pdf'] = "https://docs.google.com/spreadsheets/d/{$file_id}/export?format=pdf";
                    }
                    

                    $datos[$currentDataId] = array_merge($cadena,$datos[$currentDataId]);    

                    endif;
                }
            }    
        }
    }else{
        throw new Exception('Data not found',404);
    }

    http_response_code(200);
    $output['success'] = 1;
    $result = array_values($datos);
    array_multisort(array_column($result,1), SORT_NUMERIC, $result);

    $output['data'] = $result;

} catch (Exception $e) {

    http_response_code(404);
    $output['success'] = 0;
    $output['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
