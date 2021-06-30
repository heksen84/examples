<?php

namespace App\Console\Commands;
use App\Http\Controllers\ApiController;
use Illuminate\Console\Command;
use phpQuery;
use App\Adverts;

class ParseOlx extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:olx';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Парсинг объявлений с olx.kz';
        
    // Из первого запроса токена в браузере
    const clientId = "100302";
    const clientSecret = "dHXhnUG4QDkQ3Btx07EgdZGOYydoccbtZBE5ROlNTycHxs2W";
    const refreshToken = "81611540efea3b2e9baa2256e29df2ed79e71dc9";
    const deviceId = "c073d2b0-96ab-497a-89e0-e2bf335d2f09";
    const deviceToken = "eyJpZCI6ImMwNzNkMmIwLTk2YWItNDk3YS04OWUwLWUyYmYzMzVkMmYwOSJ9.e578f578f2de49d846e0be2a2c399bb78153c63c";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    // Обновить токен
    public function getAccessToken($refreshToken, $clientSecret, $clientId, $cookie) {

    $ch = curl_init("https://www.olx.kz/api/open/oauth/token/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"refresh_token":"'.$refreshToken.'","grant_type":"refresh_token","scope":"i2 read write v2","client_id":"'.$clientId.'","client_secret":"'.$clientSecret.'"}');
    $page = curl_exec($ch);
    curl_close($ch);
    return $page;
   }

   function getPhone($advertId, $cookie, $token) {

   $this->info("ADVERT ID: ".$advertId."\n");

    $ch = curl_init("https://www.olx.kz/api/v1/offers/".$advertId."/phones/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-With: XMLHttpRequest", "Content-Type: application/json", "Authorization: Bearer ".json_decode($token)->access_token));
    $page = curl_exec($ch); 
    curl_close($ch);
    return $page;
   }
   
   function getPage($url, $cookie) {
   
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    $page = curl_exec($ch); 
    curl_close($ch);
    return $page;
   }
   
   function getTitle($page) {
    $doc = phpQuery::newDocument($page);
    return $doc->find("h1")->text();
   }
   
   function getDesc($page) {
    $doc = phpQuery::newDocument($page);
    return $doc->find('#textContent')->text();
   }
   
   function getPrice($page) {
    $doc = phpQuery::newDocument($page);
//    $price = $doc->find('.pricelabel strong')->eq(0)->text();
    $price = $doc->find('.css-dcwlyx h3')->eq(0)->text();
    $price = str_replace("тг.", "", $price);
    $price = str_replace("₸", "", $price);
    $price = str_replace(" ", "", $price);
    return $price;
   }
   
   function getId($page) {
    $doc = phpQuery::newDocument($page);    
    return str_replace("ID: ",  "", $doc->find('.css-1ferwkx span')->eq(0)->text());

   }
   
   function getImage($page) {
    $doc = phpQuery::newDocument($page);

//    $this->info("Картинка:".$doc->find('.swiper-zoom-container img')->eq(0)->attr("src"));
    return $doc->find('.swiper-zoom-container img')->eq(0)->attr("src");
   }
   
   function getImageName($page) {
    $doc = phpQuery::newDocument($page);
    return $doc->find('#descImage img')->attr("alt");
   }

   // for ilbo
   function getCSRFToken($page) {
    $doc = phpQuery::newDocument($page);
    return $doc->find('meta')->eq(4)->attr("content");
   }

   function makeid($length) {
   
    $result = '';
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $charactersLength = strlen($characters);
    
    for ( $i = 0; $i < $length; $i++ ) {        
        $result .= $characters[rand(0,$charactersLength)-1];
    }
    
    return $result;
 }

   // метод создания объявления
   function createAdvert($advertId, $title, $desc, $price, $phoneNumber, $imgRealPath, $imgOriginalName, $categoryId, $subCategoryId, $regionId, $placeId, $optype) {        

/*    $places = [
        array("region_id" => 1, "city_id" => 11, "coords" = > "51.128207, 71.430411"), // НурСултан
        array("region_id" => 11, "city_id" => 50, "coords" = > "52.040616,76.926367")  // Аксу
    ];

    $categories = [
        array("category" => 3, "subcategory" => 18), // Настольные компьютеры
        array("category" => 3, "subcategory" => 22)  // Телефоны и гаджеты
    ];

    foreach($places as $place) {
        
        $this->info($place["region_id"]);
        $this->info($place["city_id"]);
    }    
    

    return;*/
    
    $advert_api = new ApiController();
        
    $this->info($advertId."\n");
    $this->info($title."\n");
    $this->info($desc."\n");
    $this->info($price."\n");
    $this->info("Номер: ".$phoneNumber."\n");
    $this->info($imgRealPath."\n");
    $this->info($categoryId."\n");
    $this->info($subCategoryId);
    $this->info("img_real_path: ".storage_path("app")."/".$imgRealPath);
    $this->info("img_original_name: ".$imgOriginalName);

    $dataNursultanPhonesAndGadgets = array(
        "uid" => $this->makeid(10),
        "adv_optype"=> $optype,        
        "region_id" => 1,
        "city_id" => 11,
        "adv_category" => 3,
        "adv_subcategory" => 22,        
        "adv_info" => $desc,
        "adv_price" => $price,
        "adv_phone" => $phoneNumber,
        "adv_title" => $title,        
        "adv_coords" => "51.128207, 71.430411",
        "olx_id" => $advertId,
        "img_real_path" => storage_path("app")."/".$imgRealPath,
        "img_original_name" => $imgOriginalName,
    );

    $dataNursultanComps = array(
        "uid" => $this->makeid(10),
        "adv_optype"=> $optype,        
        "region_id" => 1, // Акмол
        "city_id" => 11, // Аксу
        "adv_category" => 3,        
        "adv_subcategory" => 18, // компы
        "adv_info" => $desc,
        "adv_price" => $price,
        "adv_phone" => $phoneNumber,
        "adv_title" => $title,        
        "adv_coords" => "51.128207, 71.430411", // Akmol, Nur
        "olx_id" => $advertId,
        "img_real_path" => storage_path("app")."/".$imgRealPath,
        "img_original_name" => $imgOriginalName,
    );

    $dataAksuComps = array(
        "uid" => $this->makeid(10),
        "adv_optype"=> $optype,
        "region_id" => 11,
        "city_id" => 50,
        "adv_category" => 3,        
        "adv_subcategory" => 18, // компы
        "adv_info" => $desc,
        "adv_price" => $price,
        "adv_phone" => $phoneNumber,
        "adv_title" => $title,
        "adv_coords" => "52.040616,76.926367", // Pavl, Aksu        
        "olx_id" => $advertId,
        "img_real_path" => storage_path("app")."/".$imgRealPath,
        "img_original_name" => $imgOriginalName,
    );

    $dataAksuPhonesAndGadgets = array(
        "uid" => $this->makeid(10),
        "adv_optype"=> $optype,
        "region_id" => 11,
        "city_id" => 50,
        "adv_category" => 3,        
        "adv_subcategory" => 22,
        "adv_info" => $desc,
        "adv_price" => $price,
        "adv_phone" => $phoneNumber,
        "adv_title" => $title,
        "adv_coords" => "52.040616,76.926367", // Pavl, Aksu        
        "olx_id" => $advertId,
        "img_real_path" => storage_path("app")."/".$imgRealPath,
        "img_original_name" => $imgOriginalName,
    );

    $dataModaAndStyle = array(
        "uid" => $this->makeid(10),
        "adv_optype"=> $optype,
//        "region_id" => 11,
//        "city_id" => 50,
        "region_id" => 1, // Акмол
        "city_id" => 11, // Астана
        "adv_category" => 6,        
        "adv_subcategory" => 33,
        "adv_info" => $desc,
        "adv_price" => $price,
        "adv_phone" => $phoneNumber,
        "adv_title" => $title,
//        "adv_coords" => "52.040616,76.926367", // Pavl, Aksu        
        "adv_coords" => "51.128207, 71.430411", // Akmol, Nur
        "olx_id" => $advertId,
        "img_real_path" => storage_path("app")."/".$imgRealPath,
        "img_original_name" => $imgOriginalName,
    );

    $dataArray = [];

    //array_push($dataArray, $dataAksuComps);
//    array_push($dataArray, $dataNursultanPhonesAndGadgets);
    array_push($dataArray, $dataModaAndStyle);

    foreach($dataArray as $data) {
        //$this->info("-----------------------------------------");
        //$this->info($data["img_original_name"]);
        //$this->info("-----------------------------------------");
      $this->info($advert_api->createAdvert($data, false, null, 3)); // $data, $fromFrontend, $request, user_id
    }

   }
   
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {

        $cookie = tempnam(null, 'cookie');
                
        $token = self::getAccessToken(self::refreshToken, self::clientSecret, self::clientId, $cookie);

        $this->info($token);        
        $this->info((json_decode($token)->expires_in/3600)." часов осталось\n");


        // Сюда как-то передать массив!!!
        // Генерить оригинальное имя

       //$page = self::getPage("https://www.olx.kz/elektronika/kompyutery-i-komplektuyuschie/nastolnye-kompyutery/aksu_5689/", $cookie);
       //$page = self::getPage("https://www.olx.kz/elektronika/kompyutery-i-komplektuyuschie/nastolnye-kompyutery/astana/", $cookie);
       //$page = self::getPage("https://www.olx.kz/elektronika/telefony-i-aksesuary/mobilnye-telefony-smartfony/aksu_5689/", $cookie);	
       //$page = self::getPage("https://www.olx.kz/elektronika/telefony-i-aksesuary/mobilnye-telefony-smartfony/astana/", $cookie);
//       $page = self::getPage("https://www.olx.kz/moda-i-stil/aksessuary/aksu_5689/", $cookie);
       $page = self::getPage("https://www.olx.kz/moda-i-stil/aksessuary/astana/", $cookie);

        $this->info("ok\n");

        $doc = phpQuery::newDocument($page);

        $title = $doc->find('title')->text();
        $items = $doc->find('h3 a');


        foreach($items as $item) { 

//            $this->info(pq($item)->attr("href")."\n");
//            return;

            $page = self::getPage(pq($item)->attr("href"), $cookie);

//            $this->info($page."\n");
//            return;

            $id = trim(self::getId($page));

            $this->info("id:".$id."\n");


            $advCount = Adverts::where("olx_id", $id)->get()->count();

            if ( $advCount > 0 ) {
                $this->info("Found!\n");
                continue;
            }

            $title = trim(self::getTitle($page));

            $desc =  trim(self::getDesc($page));    
            
            $desc =  str_replace("OLX", "ilbo", $desc);
            $desc =  str_replace("olx", "ilbo", $desc);
            $desc =  str_replace("олкс", "ilbo", $desc);
            $desc =  str_replace("олэикс", "ilbo", $desc);

            $desc =  str_replace("Показать номер",  "", $desc);
            $desc =  str_replace("877",  "", $desc);
            $desc =  str_replace("870",  "", $desc);
            $desc =  str_replace("+777",  "", $desc);
            $desc =  str_replace("+ 777",  "", $desc);
            $desc =  str_replace("+770",  "", $desc);
            $desc =  str_replace("Звонить по номеру",  "", $desc);
            $desc =  str_replace("-  - ,  -  -",  "", $desc);
            $desc =  str_replace("-  -",  "", $desc);
          
            $price = trim(self::getPrice($page));
          
            // price может быть Обмен                                            

            $optype = 1;

            if ($price=="Обмен") {
                $price = null;
                $optype = 2;
            }

          $this->info("Цена: ".$price);
		
            $phoneJson = self::getPhone($id, $cookie, $token);

          $this->info("-----------[ телефон ]-----------");
          $this->info($phoneJson);
          $this->info("---------------------------------");

            $phoneData = json_decode($phoneJson)->data;
//            $phoneData = json_decode($phoneJson);
            
            if ($phoneData)
                $phoneDecode = $phoneData->phones;

            if ($phoneJson && count($phoneDecode) > 0) {

            $phone = $phoneDecode[0];   
          
            $phone = str_replace("[",  "", $phone);
            $phone = str_replace("]",  "", $phone);
            $phone = str_replace("(",  "", $phone);
            $phone = str_replace(")",  "", $phone);
            $phone = str_replace(" ",  "", $phone);
            $phone = str_replace("-",  "", $phone);
            //$phone = str_replace("+7", "", $phone);
            $phone = str_replace("+",  "", $phone);
          
            if ($phone[0] === "8")
              $phone = substr($phone, 1);            
            }
            else
                $phone = null;     
                
                if ($phone!=null) {
                    $phone = "(".$phone[0].$phone[1].$phone[2].")".$phone[3].$phone[4].$phone[5].$phone[6].$phone[7].$phone[8].$phone[9];
                    $this->info("Phone result: ".$phone."\n");
                }

            $imageUrl = self::getImage($page);

	    $imgRealPath = null;
	    $imgOriginalName = null;
            
            if ($imageUrl) {

                // @ - disable error
                $image = @file_get_contents($imageUrl, 0, stream_context_create(["http"=>["timeout" => 3]]));

                if ($image===false)
                    continue;
                else {
                    $imgOriginalName = self::getImageName($page).".webp";
                    $imgRealPath = 'images/parse/olx/'.$imgOriginalName;
                    @\Storage::disk('local')->put($imgRealPath, $image);
                }
            }
            else 
                $imageUrl = null;

            if ($phone != "") {                
                self::createAdvert($id, $title, $desc, $price, $phone, $imgRealPath, $imgOriginalName, 0, 0, 0, 0, $optype);
            }

        }
    }    
}