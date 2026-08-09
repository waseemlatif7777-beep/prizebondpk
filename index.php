<?php
/**
 * ╔══════════════════════════════════════════════════════════╗
 * ║        PRIZE BOND PK — Complete Website                 ║
 * ║        Upload this single file to cPanel public_html    ║
 * ║        Database tables are created automatically        ║
 * ╚══════════════════════════════════════════════════════════╝
 *
 * SETUP:
 *  1. Create a MySQL database in cPanel
 *  2. Edit the CONFIG section below (lines ~20-30)
 *  3. Upload this file to public_html/
 *  4. Visit your domain — tables are created on first visit
 *  5. Go to ?page=admin to add draw results
 */

// ════════════════════════════════════════════════════════════
// ■  CONFIGURATION  — Edit before uploading
// ════════════════════════════════════════════════════════════
define('DB_HOST',     'localhost');
define('DB_NAME',     'prizebond_db');       // Your cPanel DB name
define('DB_USER',     'db_username');         // Your cPanel DB user
define('DB_PASS',     'db_password');         // Your cPanel DB password
define('ADMIN_PASS',  'Admin@PrizeBond2025'); // Change this!
define('SITE_NAME',   'Prize Bond PK');
define('SITE_TAGLINE','Pakistan\'s #1 Prize Bond Result Website');
define('SITE_URL',    (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']),'/'));

// ════════════════════════════════════════════════════════════
// ■  SESSION & ERROR HANDLING
// ════════════════════════════════════════════════════════════
session_start();
error_reporting(0);
ini_set('display_errors', 0);

// ════════════════════════════════════════════════════════════
// ■  DATABASE CONNECTION
// ════════════════════════════════════════════════════════════
$pdo      = null;
$db_error = '';
try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_OBJ,
         PDO::ATTR_EMULATE_PREPARES=>false]
    );
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

// ════════════════════════════════════════════════════════════
// ■  AUTO-SETUP TABLES (first run only)
// ════════════════════════════════════════════════════════════
if ($pdo && !isset($_SESSION['pb_setup'])) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pb_bond_types`(
          `id` int NOT NULL AUTO_INCREMENT,`denomination` int NOT NULL,
          `name` varchar(100) NOT NULL,`slug` varchar(60) NOT NULL,
          `is_premium` tinyint DEFAULT 0,
          `first_prize_amount` bigint DEFAULT 0,`first_prize_count` int DEFAULT 1,
          `second_prize_amount` bigint DEFAULT 0,`second_prize_count` int DEFAULT 3,
          `third_prize_amount` bigint DEFAULT 0,`third_prize_count` int DEFAULT 1696,
          `draws_per_year` int DEFAULT 4,`is_active` tinyint DEFAULT 1,
          PRIMARY KEY(`id`),UNIQUE KEY`uk_d`(`denomination`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `pb_draws`(
          `id` int NOT NULL AUTO_INCREMENT,`bond_type_id` int NOT NULL,
          `draw_number` varchar(20) NOT NULL,`draw_date` date NOT NULL,
          `city` varchar(100) NOT NULL,`status` enum('published','draft') DEFAULT 'published',
          `pdf_url` varchar(500) DEFAULT NULL,`total_winners` int DEFAULT 0,
          `views` int DEFAULT 0,`created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY(`id`),KEY`i1`(`bond_type_id`),KEY`i2`(`draw_date`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `pb_winners`(
          `id` int NOT NULL AUTO_INCREMENT,`draw_id` int NOT NULL,
          `prize_type` enum('first','second','third') NOT NULL,
          `winning_number` varchar(20) NOT NULL,`prize_amount` bigint DEFAULT 0,
          PRIMARY KEY(`id`),KEY`i1`(`draw_id`),KEY`i2`(`winning_number`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `pb_schedules`(
          `id` int NOT NULL AUTO_INCREMENT,`bond_type_id` int NOT NULL,
          `draw_number` varchar(20) NOT NULL,`draw_date` date NOT NULL,
          `city` varchar(100) NOT NULL,`venue` varchar(255) DEFAULT NULL,
          `status` enum('upcoming','completed','cancelled') DEFAULT 'upcoming',
          PRIMARY KEY(`id`),KEY`i1`(`draw_date`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `pb_subscribers`(
          `id` int NOT NULL AUTO_INCREMENT,`email` varchar(255) NOT NULL,
          `name` varchar(100) DEFAULT NULL,`subscribed_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `is_active` tinyint DEFAULT 1,
          PRIMARY KEY(`id`),UNIQUE KEY`uk_e`(`email`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `pb_search_log`(
          `id` int NOT NULL AUTO_INCREMENT,`bond_number` varchar(20) NOT NULL,
          `bond_type_id` int DEFAULT NULL,`is_winner` tinyint DEFAULT 0,
          `searched_at` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY(`id`),KEY`i1`(`bond_number`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Seed bond types
        if ((int)$pdo->query("SELECT COUNT(*) FROM pb_bond_types")->fetchColumn()===0) {
            $s=$pdo->prepare("INSERT IGNORE INTO pb_bond_types(denomination,name,slug,is_premium,first_prize_amount,first_prize_count,second_prize_amount,second_prize_count,third_prize_amount,third_prize_count,draws_per_year)VALUES(?,?,?,?,?,?,?,?,?,?,?)");
            foreach([
                [100,'Rs.100 Prize Bond','prize-bond-100',0,700000,1,200000,3,1000,1199,4],
                [200,'Rs.200 Prize Bond','prize-bond-200',0,750000,1,250000,5,1250,2394,4],
                [750,'Rs.750 Prize Bond','prize-bond-750',0,1500000,1,500000,3,9300,1696,4],
                [1500,'Rs.1500 Prize Bond','prize-bond-1500',0,3000000,1,1000000,3,18500,1696,4],
                [3000,'Rs.3000 Prize Bond','prize-bond-3000',0,6000000,1,2000000,3,40000,1696,4],
                [7500,'Rs.7500 Prize Bond','prize-bond-7500',0,15000000,1,5000000,3,93000,1696,4],
                [15000,'Rs.15000 Prize Bond','prize-bond-15000',0,30000000,1,10000000,3,185000,1696,4],
                [25000,'Rs.25000 Premium Prize Bond','prize-bond-25000',1,50000000,1,15000000,3,312000,1696,2],
                [40000,'Rs.40000 Premium Prize Bond','prize-bond-40000',1,80000000,1,30000000,3,500000,1696,2],
            ] as $b) $s->execute($b);
        }
        $_SESSION['pb_setup']=1;
    } catch(PDOException $e){}
}

// ════════════════════════════════════════════════════════════
// ■  HELPER FUNCTIONS
// ════════════════════════════════════════════════════════════
function e(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function fmt(int $n):string{
    if($n>=10000000)return'Rs.'.rtrim(rtrim(number_format($n/10000000,2),'0'),'.').' Cr';
    if($n>=100000)  return'Rs.'.rtrim(rtrim(number_format($n/100000, 2),'0'),'.').' Lac';
    return'Rs.'.number_format($n);
}
function sp(array $p=[]):string{
    $b=strtok($_SERVER['REQUEST_URI']??'/?','?');
    $cur=[];parse_str($_SERVER['QUERY_STRING']??'',$cur);
    unset($cur['ajax']);
    return$b.'?'.http_build_query(array_merge($cur,$p));
}
function cities():array{return['Karachi','Lahore','Islamabad','Rawalpindi','Faisalabad','Peshawar','Quetta','Multan','Hyderabad','Sialkot','Gujranwala','Bahawalpur','Sukkur','Larkana','Mirpur AJK'];}

function db_bond_types():array{
    global$pdo;if(!$pdo)return[];
    return $pdo->query("SELECT*FROM pb_bond_types WHERE is_active=1 ORDER BY denomination")->fetchAll()?:[];
}
function db_bond_type_by_slug(string $slug):?object{
    global$pdo;if(!$pdo)return null;
    $s=$pdo->prepare("SELECT*FROM pb_bond_types WHERE slug=?");$s->execute([$slug]);return$s->fetch()?:null;
}
function db_bond_type(int $id):?object{
    global$pdo;if(!$pdo)return null;
    $s=$pdo->prepare("SELECT*FROM pb_bond_types WHERE id=?");$s->execute([$id]);return$s->fetch()?:null;
}
function db_latest_per_type():array{
    global$pdo;if(!$pdo)return[];
    return$pdo->query("SELECT d.*,bt.denomination,bt.name AS bond_name,bt.slug AS bond_slug,bt.is_premium,bt.first_prize_amount FROM pb_draws d JOIN pb_bond_types bt ON bt.id=d.bond_type_id WHERE d.status='published' AND d.id IN(SELECT MAX(id)FROM pb_draws WHERE status='published' GROUP BY bond_type_id) ORDER BY bt.denomination")->fetchAll()?:[];
}
function db_draws_by_type(int $btid,int $limit=10,int $off=0):array{
    global$pdo;if(!$pdo)return[];
    $s=$pdo->prepare("SELECT d.*,bt.denomination,bt.name AS bond_name,bt.first_prize_amount FROM pb_draws d JOIN pb_bond_types bt ON bt.id=d.bond_type_id WHERE d.bond_type_id=? AND d.status='published' ORDER BY d.draw_date DESC LIMIT? OFFSET?");
    $s->execute([$btid,$limit,$off]);return$s->fetchAll()?:[];
}
function db_count_draws(int $btid):int{
    global$pdo;if(!$pdo)return 0;
    $s=$pdo->prepare("SELECT COUNT(*)FROM pb_draws WHERE bond_type_id=? AND status='published'");$s->execute([$btid]);return(int)$s->fetchColumn();
}
function db_draw(int $id):?object{
    global$pdo;if(!$pdo)return null;
    $s=$pdo->prepare("SELECT d.*,bt.denomination,bt.name AS bond_name,bt.slug AS bond_slug,bt.is_premium,bt.first_prize_amount,bt.second_prize_amount,bt.third_prize_amount,bt.first_prize_count,bt.second_prize_count,bt.third_prize_count FROM pb_draws d JOIN pb_bond_types bt ON bt.id=d.bond_type_id WHERE d.id=?");
    $s->execute([$id]);$r=$s->fetch()?:null;
    if($r)$pdo->prepare("UPDATE pb_draws SET views=views+1 WHERE id=?")->execute([$id]);
    return$r;
}
function db_winners(int $did,string $pt=''):array{
    global$pdo;if(!$pdo)return[];
    $w='draw_id=?';$p=[$did];
    if($pt){$w.=' AND prize_type=?';$p[]=$pt;}
    $s=$pdo->prepare("SELECT*FROM pb_winners WHERE $w ORDER BY prize_type,winning_number");$s->execute($p);return$s->fetchAll()?:[];
}
function db_search(string $num,int $btid=0,int $did=0):array{
    global$pdo;if(!$pdo)return[];
    $num=preg_replace('/\D/','',$num);if(!$num)return[];
    if($did<1&&$btid>0){
        $s=$pdo->prepare("SELECT id FROM pb_draws WHERE bond_type_id=? AND status='published' ORDER BY draw_date DESC LIMIT 1");
        $s->execute([$btid]);$did=(int)$s->fetchColumn();
    }
    $w='w.winning_number=?';$p=[$num];
    if($did>0){$w.=' AND w.draw_id=?';$p[]=$did;}
    $s=$pdo->prepare("SELECT w.*,d.draw_date,d.draw_number,d.city,bt.denomination,bt.name AS bond_name,bt.slug FROM pb_winners w JOIN pb_draws d ON d.id=w.draw_id JOIN pb_bond_types bt ON bt.id=d.bond_type_id WHERE $w ORDER BY d.draw_date DESC LIMIT 10");
    $s->execute($p);$res=$s->fetchAll()?:[];
    try{$pdo->prepare("INSERT INTO pb_search_log(bond_number,bond_type_id,is_winner)VALUES(?,?,?)")->execute([$num,$btid?:null,count($res)>0?1:0]);}catch(Exception $e){}
    return$res;
}
function db_schedules(string $st='upcoming',int $lim=20):array{
    global$pdo;if(!$pdo)return[];
    $s=$pdo->prepare("SELECT s.*,bt.denomination,bt.name AS bond_name,bt.slug AS bond_slug,bt.is_premium FROM pb_schedules s JOIN pb_bond_types bt ON bt.id=s.bond_type_id WHERE s.status=? ORDER BY s.draw_date ASC LIMIT?");
    $s->execute([$st,$lim]);return$s->fetchAll()?:[];
}
function db_stats():array{
    global$pdo;if(!$pdo)return['draws'=>0,'winners'=>0,'types'=>0,'searches'=>0];
    return['draws'=>(int)$pdo->query("SELECT COUNT(*)FROM pb_draws WHERE status='published'")->fetchColumn(),
           'winners'=>(int)$pdo->query("SELECT COALESCE(SUM(total_winners),0)FROM pb_draws WHERE status='published'")->fetchColumn(),
           'types'=>(int)$pdo->query("SELECT COUNT(*)FROM pb_bond_types WHERE is_active=1")->fetchColumn(),
           'searches'=>(int)$pdo->query("SELECT COUNT(*)FROM pb_search_log")->fetchColumn()];
}
function db_all_draws(int $lim=20,int $off=0):array{
    global$pdo;if(!$pdo)return[];
    $s=$pdo->prepare("SELECT d.*,bt.denomination,bt.name AS bond_name FROM pb_draws d JOIN pb_bond_types bt ON bt.id=d.bond_type_id ORDER BY d.draw_date DESC LIMIT? OFFSET?");
    $s->execute([$lim,$off]);return$s->fetchAll()?:[];
}

// ════════════════════════════════════════════════════════════
// ■  AJAX HANDLER — exits early
// ════════════════════════════════════════════════════════════
if(isset($_GET['ajax'])){
    header('Content-Type: application/json; charset=utf-8');
    $act=$_GET['action']??'';
    switch($act){
        case 'search':
            $n=preg_replace('/\D/','',trim($_POST['number']??''));
            $bt=(int)($_POST['bond_type_id']??0);
            $di=(int)($_POST['draw_id']??0);
            if(!$n){echo json_encode(['ok'=>false,'msg'=>'Enter a valid bond number.']);exit;}
            $res=db_search($n,$bt,$di);
            if(!$res){echo json_encode(['ok'=>true,'found'=>false,'number'=>$n]);exit;}
            $out=[];foreach($res as $r)$out[]=['found'=>true,'number'=>$r->winning_number,'prize_type'=>ucfirst($r->prize_type),'prize_amount'=>fmt((int)$r->prize_amount),'draw_number'=>$r->draw_number,'draw_date'=>date('d M Y',strtotime($r->draw_date)),'city'=>$r->city,'bond_name'=>$r->bond_name];
            echo json_encode(['ok'=>true,'found'=>true,'results'=>$out]);exit;

        case 'bulk':
            $raw=$_POST['numbers']??'';
            $bt=(int)($_POST['bond_type_id']??0);
            $di=(int)($_POST['draw_id']??0);
            $nums=array_unique(array_filter(array_map(fn($n)=>preg_replace('/\D/','',trim($n)),preg_split('/[\n,\s]+/',$raw))));
            if(count($nums)>500){echo json_encode(['ok'=>false,'msg'=>'Max 500 numbers per search.']);exit;}
            if(!$nums){echo json_encode(['ok'=>false,'msg'=>'No valid numbers found.']);exit;}
            $wins=[];$lost=[];
            foreach($nums as $n){
                if(!$n)continue;
                $r=db_search($n,$bt,$di);
                if($r)$wins[]=['number'=>$n,'prize_type'=>ucfirst($r[0]->prize_type),'prize_amount'=>fmt((int)$r[0]->prize_amount),'bond_name'=>$r[0]->bond_name];
                else $lost[]=$n;
            }
            echo json_encode(['ok'=>true,'winners'=>$wins,'losers'=>$lost,'total'=>count($nums)]);exit;

        case 'subscribe':
            $em=filter_var($_POST['email']??'',FILTER_SANITIZE_EMAIL);
            $nm=strip_tags($_POST['name']??'');
            if(!filter_var($em,FILTER_VALIDATE_EMAIL)){echo json_encode(['ok'=>false,'msg'=>'Enter a valid email address.']);exit;}
            try{
                $pdo->prepare("INSERT INTO pb_subscribers(email,name)VALUES(?,?)ON DUPLICATE KEY UPDATE is_active=1")->execute([$em,$nm]);
                echo json_encode(['ok'=>true,'msg'=>'Subscribed! We\'ll notify you of new draw results.']);
            }catch(Exception $e){echo json_encode(['ok'=>false,'msg'=>'Could not subscribe. Please try again.']);}
            exit;

        case 'get_draws':
            $bt=(int)($_POST['bond_type_id']??0);
            if($bt<1){echo json_encode(['ok'=>false]);exit;}
            $s=$pdo->prepare("SELECT id,draw_number,draw_date FROM pb_draws WHERE bond_type_id=? AND status='published' ORDER BY draw_date DESC LIMIT 50");
            $s->execute([$bt]);$draws=$s->fetchAll();
            $opt='<option value="">All Draws</option>';
            foreach($draws as $d)$opt.='<option value="'.e($d->id).'">Draw #'.e($d->draw_number).' – '.date('d M Y',strtotime($d->draw_date)).'</option>';
            echo json_encode(['ok'=>true,'options'=>$opt]);exit;

        default:echo json_encode(['ok'=>false,'msg'=>'Unknown action']);exit;
    }
}

// ════════════════════════════════════════════════════════════
// ■  ADMIN AUTHENTICATION & ACTIONS
// ════════════════════════════════════════════════════════════
$is_admin=isset($_SESSION['pb_admin'])&&$_SESSION['pb_admin']===true;
$adm_msg='';$adm_type='success';

if(isset($_GET['logout'])){session_destroy();header('Location:'.sp(['page'=>'admin']));exit;}

if(isset($_POST['login_submit'])){
    if(($_POST['pass']??'')===ADMIN_PASS){
        $_SESSION['pb_admin']=true;$_SESSION['adm_token']=bin2hex(random_bytes(16));
        header('Location:'.sp(['page'=>'admin']));exit;
    }else{$adm_msg='Wrong password.';$adm_type='danger';}
}

if($is_admin&&isset($_POST['save_draw'])){
    if(($_POST['_tok']??'')!==($_SESSION['adm_token']??'')){$adm_msg='Token error.';$adm_type='danger';}
    else{
        $did=(int)($_POST['draw_id']??0);
        $bt=(int)$_POST['bond_type_id'];
        $dn=strip_tags($_POST['draw_number']??'');
        $dd=strip_tags($_POST['draw_date']??'');
        $ct=strip_tags($_POST['city']??'');
        $st=in_array($_POST['status']??'',['published','draft'])?$_POST['status']:'published';
        $pu=filter_var($_POST['pdf_url']??'',FILTER_SANITIZE_URL);
        if(!$bt||!$dn||!$dd||!$ct){$adm_msg='Fill all required fields.';$adm_type='danger';}
        else{
            try{
                if($did>0){
                    $pdo->prepare("UPDATE pb_draws SET bond_type_id=?,draw_number=?,draw_date=?,city=?,status=?,pdf_url=? WHERE id=?")->execute([$bt,$dn,$dd,$ct,$st,$pu,$did]);
                }else{
                    $pdo->prepare("INSERT INTO pb_draws(bond_type_id,draw_number,draw_date,city,status,pdf_url)VALUES(?,?,?,?,?,?)")->execute([$bt,$dn,$dd,$ct,$st,$pu]);
                    $did=(int)$pdo->lastInsertId();
                }
                $btr=db_bond_type($bt);
                $pdo->prepare("DELETE FROM pb_winners WHERE draw_id=?")->execute([$did]);
                $total=0;
                foreach(['first','second','third'] as $pt){
                    $raw=$_POST['winners_'.$pt]??'';
                    $nums=array_filter(array_map('trim',preg_split('/[\n,\s]+/',$raw)));
                    $amt=match($pt){'first'=>(int)($btr->first_prize_amount??0),'second'=>(int)($btr->second_prize_amount??0),'third'=>(int)($btr->third_prize_amount??0)};
                    $ins=$pdo->prepare("INSERT INTO pb_winners(draw_id,prize_type,winning_number,prize_amount)VALUES(?,?,?,?)");
                    foreach($nums as $n){$n=preg_replace('/\D/','',$n);if($n){$ins->execute([$did,$pt,$n,$amt]);$total++;}}
                }
                $pdo->prepare("UPDATE pb_draws SET total_winners=? WHERE id=?")->execute([$total,$did]);
                $adm_msg='Draw saved! '.$total.' winning numbers stored.';
            }catch(Exception $e){$adm_msg='DB error: '.$e->getMessage();$adm_type='danger';}
        }
    }
}

if($is_admin&&isset($_GET['del_draw'])){
    $did=(int)$_GET['del_draw'];
    if($did>0){
        $pdo->prepare("DELETE FROM pb_winners WHERE draw_id=?")->execute([$did]);
        $pdo->prepare("DELETE FROM pb_draws WHERE id=?")->execute([$did]);
        $adm_msg='Draw deleted.';
    }
}

if($is_admin&&isset($_POST['save_schedule'])){
    $bt=(int)$_POST['bond_type_id'];
    $dn=strip_tags($_POST['draw_number']??'');
    $dd=strip_tags($_POST['draw_date']??'');
    $ct=strip_tags($_POST['city']??'');
    $vn=strip_tags($_POST['venue']??'');
    if($bt&&$dn&&$dd&&$ct){
        $pdo->prepare("INSERT INTO pb_schedules(bond_type_id,draw_number,draw_date,city,venue)VALUES(?,?,?,?,?)")->execute([$bt,$dn,$dd,$ct,$vn]);
        $adm_msg='Schedule entry added.';
    }else{$adm_msg='Fill all required fields.';$adm_type='danger';}
}

// ════════════════════════════════════════════════════════════
// ■  PAGE ROUTING
// ════════════════════════════════════════════════════════════
$page       = $_GET['page']??'home';
$bond_slug  = $_GET['type']??'';
$draw_id_p  = (int)($_GET['id']??0);
$adm_sub    = $_GET['sub']??'draws';

$bond_types   = db_bond_types();
$latest_draws = db_latest_per_type();
$schedules    = db_schedules('upcoming',8);
$stats        = db_stats();

$draw_detail = null;
if($page==='draw'&&$draw_id_p>0) $draw_detail=db_draw($draw_id_p);

$cur_bond=$bond_draws=[];
$bp=max(1,(int)($_GET['pg']??1));$bpp=10;
if($page==='bond'&&$bond_slug){
    $cur_bond=db_bond_type_by_slug($bond_slug);
    if($cur_bond)$bond_draws=db_draws_by_type($cur_bond->id,$bpp,($bp-1)*$bpp);
}

$page_titles=[
    'home'     =>SITE_NAME.' – '.SITE_TAGLINE,
    'search'   =>'Search Prize Bond Number | '.SITE_NAME,
    'schedule' =>'Upcoming Draw Schedule 2025 | '.SITE_NAME,
    'bond'     =>($cur_bond?e($cur_bond->name).' Results':'Bond Results').' | '.SITE_NAME,
    'draw'     =>($draw_detail?e($draw_detail->bond_name).' Draw #'.e($draw_detail->draw_number):' Draw Result').' | '.SITE_NAME,
    'admin'    =>'Admin Panel | '.SITE_NAME,
];
$ptitle=$page_titles[$page]??SITE_NAME.' | Prize Bond Results';

// ════════════════════════════════════════════════════════════
// ■  BEGIN HTML
// ════════════════════════════════════════════════════════════
?><!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Check Pakistan Prize Bond results online. Search by bond number, view complete winner lists, Rs.100 to Rs.40000 all denominations.">
<meta name="keywords" content="prize bond pakistan, prize bond result, prize bond check, SBP prize bond, 100 prize bond, 750 prize bond">
<meta property="og:title" content="<?=e($ptitle)?>">
<meta property="og:description" content="Pakistan's #1 Prize Bond Result Website. Instant search, all denominations.">
<meta property="og:type" content="website">
<meta name="theme-color" content="#0B8F3A">
<title><?=e($ptitle)?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
/* ╔═══════════════════════════════════════════════════╗
   ║  PRIZE BOND PK — Complete Stylesheet              ║
   ╚═══════════════════════════════════════════════════╝ */

/* ── 1. CSS Variables ─────────────────────────────── */
:root{
  --pk:#0B8F3A;--pk-d:#076B2B;--pk-m:#1DA852;--pk-l:#E8F5EE;--pk-p:#F0FAF4;
  --white:#fff;--dark:#1A1A2E;--g900:#111827;--g700:#374151;--g500:#6B7280;
  --g300:#D1D5DB;--g100:#F3F4F6;
  --text:#1F2937;--muted:#6B7280;--bg:#F8FAFC;--surface:#fff;--border:#E5E7EB;
  --gold:#F59E0B;--silver:#64748B;--bronze:#B45309;
  --sh:0 1px 3px rgba(0,0,0,.07);
  --sh-md:0 4px 14px rgba(0,0,0,.09);
  --sh-lg:0 10px 32px rgba(0,0,0,.11);
  --sh-green:0 4px 20px rgba(11,143,58,.22);
  --r:12px;--r-sm:8px;--r-lg:18px;--r-xl:26px;
  --tr:all .24s cubic-bezier(.4,0,.2,1);
  --font:'Plus Jakarta Sans',sans-serif;
  --font-body:'Inter',sans-serif;
  --font-mono:'JetBrains Mono',monospace;
}
[data-theme="dark"]{
  --text:#F1F5F9;--muted:#94A3B8;--bg:#0F172A;--surface:#1E293B;
  --border:#334155;--g100:#1E293B;--pk-l:#0F2A1A;--pk-p:#0A1F12;
  --sh:0 1px 3px rgba(0,0,0,.4);--sh-md:0 4px 14px rgba(0,0,0,.45);
  --sh-lg:0 10px 32px rgba(0,0,0,.55);--white:#1E293B;
}

/* ── 2. Base ──────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font-body);background:var(--bg);color:var(--text);font-size:15px;line-height:1.65;transition:background .3s,color .3s}
h1,h2,h3,h4,h5,h6{font-family:var(--font);font-weight:700;color:var(--text);line-height:1.3}
a{color:var(--pk);text-decoration:none;transition:var(--tr)}
a:hover{color:var(--pk-d)}
img{max-width:100%;height:auto}
.mono{font-family:var(--font-mono)}
.text-pk{color:var(--pk)!important}
.bg-pk{background:var(--pk)!important}

/* ── 3. Navbar ────────────────────────────────────── */
.pb-nav{background:var(--pk);box-shadow:0 2px 12px rgba(0,0,0,.18);position:sticky;top:0;z-index:1000;padding:0}
.pb-nav .container-fluid{padding:0 1.5rem}
.pb-nav .navbar-brand{font-family:var(--font);font-weight:800;font-size:1.35rem;color:#fff!important;letter-spacing:-.3px;padding:.75rem 0}
.pb-nav .navbar-brand span{color:#FFD700}
.pb-nav .nav-link{color:rgba(255,255,255,.88)!important;font-weight:500;font-size:.875rem;padding:.75rem .85rem!important;border-radius:var(--r-sm);margin:0 .1rem;transition:var(--tr)}
.pb-nav .nav-link:hover,.pb-nav .nav-link.active{color:#fff!important;background:rgba(255,255,255,.15)}
.nav-btn{background:rgba(255,255,255,.18);color:#fff!important;border:1px solid rgba(255,255,255,.3);border-radius:var(--r-sm);padding:.45rem 1rem!important;font-weight:600}
.nav-btn:hover{background:rgba(255,255,255,.28)!important}
.dark-btn{background:none;border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:var(--r-sm);padding:.4rem .65rem;cursor:pointer;transition:var(--tr);font-size:1rem}
.dark-btn:hover{background:rgba(255,255,255,.15)}
.navbar-toggler{border-color:rgba(255,255,255,.4);padding:.3rem .6rem}
.navbar-toggler-icon{filter:brightness(0) invert(1)}

/* ── 4. Hero ──────────────────────────────────────── */
.pb-hero{background:linear-gradient(135deg,#065225 0%,var(--pk) 45%,#1DA852 100%);padding:4.5rem 0 3.5rem;position:relative;overflow:hidden}
.pb-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
.pb-hero::after{content:'';position:absolute;bottom:-2px;left:0;right:0;height:60px;background:var(--bg);clip-path:ellipse(55% 100% at 50% 100%)}
.hero-badge{display:inline-block;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:.8rem;font-weight:600;padding:.3rem .85rem;border-radius:50px;margin-bottom:1.2rem;letter-spacing:.5px;backdrop-filter:blur(8px)}
.hero-title{font-size:clamp(1.8rem,4vw,3.2rem);color:#fff;font-weight:800;margin-bottom:.8rem;line-height:1.2;text-shadow:0 2px 8px rgba(0,0,0,.2)}
.hero-sub{color:rgba(255,255,255,.85);font-size:1.05rem;margin-bottom:2rem;max-width:560px;margin-left:auto;margin-right:auto}
.hero-search-box{background:rgba(255,255,255,.13);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.25);border-radius:var(--r-xl);padding:1.5rem;max-width:680px;margin:0 auto 1.8rem;box-shadow:0 8px 32px rgba(0,0,0,.2)}
.hero-search-box .form-control,.hero-search-box .form-select{border-radius:var(--r-sm);border:1.5px solid var(--border);font-family:var(--font);font-size:1rem;height:52px;background:rgba(255,255,255,.95)}
.hero-search-box .form-control:focus,.hero-search-box .form-select:focus{border-color:var(--pk);box-shadow:0 0 0 3px rgba(11,143,58,.18)}
.btn-hero-search{background:#fff;color:var(--pk);border:none;border-radius:var(--r-sm);height:52px;padding:0 1.8rem;font-weight:700;font-family:var(--font);font-size:1rem;transition:var(--tr);white-space:nowrap}
.btn-hero-search:hover{background:var(--pk-l);color:var(--pk-d);transform:translateY(-1px)}
.quick-types{display:flex;flex-wrap:wrap;gap:.5rem;justify-content:center}
.quick-type-btn{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:.78rem;font-weight:600;padding:.35rem .85rem;border-radius:50px;cursor:pointer;transition:var(--tr);text-decoration:none}
.quick-type-btn:hover{background:rgba(255,255,255,.28);color:#fff;transform:translateY(-2px)}

/* ── 5. Section Layout ────────────────────────────── */
.pb-section{padding:3.5rem 0}
.section-eyebrow{font-size:.8rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--pk);margin-bottom:.5rem}
.section-title{font-size:clamp(1.5rem,3vw,2.1rem);font-weight:800;margin-bottom:.5rem}
.section-sub{color:var(--muted);font-size:.95rem;margin-bottom:2.5rem;max-width:540px}

/* ── 6. Cards ─────────────────────────────────────── */
.pb-card{background:var(--surface);border-radius:var(--r);box-shadow:var(--sh);border:1px solid var(--border);transition:var(--tr)}
.pb-card:hover{box-shadow:var(--sh-md);transform:translateY(-3px)}
.draw-card{position:relative;overflow:hidden}
.draw-card::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--pk)}
.draw-card .badge-denom{background:var(--pk-l);color:var(--pk);font-size:.72rem;font-weight:700;padding:.3rem .65rem;border-radius:50px;display:inline-block;margin-bottom:.6rem}
.draw-card .draw-num{font-family:var(--font);font-weight:800;font-size:1.5rem;color:var(--text);line-height:1.1}
.draw-card .meta{font-size:.8rem;color:var(--muted);margin:.3rem 0 .7rem}
.draw-card .prize-label{font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.8px}
.draw-card .prize-val{font-size:.95rem;font-weight:700;color:var(--pk);font-family:var(--font)}
.draw-card .btn-view{font-size:.8rem;padding:.35rem .85rem;border-radius:50px;font-weight:600}
.draw-card.premium::before{background:var(--gold)}
.draw-card.premium .badge-denom{background:rgba(245,158,11,.12);color:#B45309}

/* ── 7. Bond Category Cards ───────────────────────── */
.bond-cat-card{cursor:pointer;border-radius:var(--r-lg);padding:1.5rem;border:1.5px solid var(--border);background:var(--surface);transition:var(--tr);position:relative;overflow:hidden;text-align:left}
.bond-cat-card::after{content:'';position:absolute;bottom:-20px;right:-20px;width:90px;height:90px;border-radius:50%;opacity:.08;background:currentColor;transition:var(--tr)}
.bond-cat-card:hover{transform:translateY(-5px);box-shadow:var(--sh-green);border-color:var(--pk);background:var(--pk-p)}
.bond-cat-card:hover::after{transform:scale(1.4)}
.bond-cat-card .icon-wrap{width:48px;height:48px;border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:1rem}
.bond-cat-card .cat-name{font-family:var(--font);font-weight:700;font-size:1rem;margin-bottom:.25rem;color:var(--text)}
.bond-cat-card .cat-prize{font-size:.8rem;color:var(--muted)}
.bond-cat-card .cat-prize strong{color:var(--pk);font-weight:700}
.bond-cat-card .premium-badge{position:absolute;top:.75rem;right:.75rem;background:var(--gold);color:#fff;font-size:.65rem;font-weight:700;padding:.2rem .5rem;border-radius:50px;letter-spacing:.5px}

/* ── 8. Number Chips ─────────────────────────────── */
.number-chip{display:inline-block;font-family:var(--font-mono);font-weight:700;font-size:.88rem;padding:.3rem .7rem;border-radius:var(--r-sm);border:1.5px solid;margin:.2rem;letter-spacing:.05em;transition:var(--tr)}
.chip-first{border-color:var(--gold);background:rgba(245,158,11,.1);color:#92400E}
.chip-second{border-color:var(--silver);background:rgba(100,116,139,.1);color:var(--silver)}
.chip-third{border-color:var(--pk);background:var(--pk-l);color:var(--pk-d)}
.chip-winner{animation:pulse-win 1s ease-in-out;border-color:var(--gold)!important;background:rgba(245,158,11,.15)!important;color:#92400E!important;box-shadow:0 0 0 3px rgba(245,158,11,.25)}
@keyframes pulse-win{0%{transform:scale(1)}50%{transform:scale(1.1)}100%{transform:scale(1)}}

/* ── 9. Search ────────────────────────────────────── */
.search-section-bg{background:linear-gradient(135deg,var(--pk-p) 0%,var(--pk-l) 100%)}
.search-tabs .nav-link{color:var(--muted);border-radius:var(--r-sm);font-weight:600;font-size:.875rem;padding:.55rem 1.2rem}
.search-tabs .nav-link.active{background:var(--pk);color:#fff!important}
.search-tabs .nav-link:hover:not(.active){color:var(--pk);background:var(--pk-l)}
.search-result-card{border-radius:var(--r);padding:1.5rem;margin-top:1.2rem;border:2px solid;animation:fadeSlide .3s ease}
.result-won{border-color:var(--gold);background:rgba(245,158,11,.06)}
.result-lost{border-color:var(--border);background:var(--surface)}
@keyframes fadeSlide{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

/* ── 10. Schedule ─────────────────────────────────── */
.schedule-table{border-radius:var(--r);overflow:hidden;border:1px solid var(--border)}
.schedule-table thead{background:var(--pk);color:#fff}
.schedule-table tbody tr{background:var(--surface);border-bottom:1px solid var(--border);transition:var(--tr)}
.schedule-table tbody tr:hover{background:var(--pk-p)}
.schedule-table td,.schedule-table th{padding:.85rem 1rem;font-size:.875rem}
.countdown-box{display:flex;gap:.3rem;align-items:center;flex-wrap:wrap}
.cd-unit{background:var(--pk);color:#fff;border-radius:var(--r-sm);padding:.2rem .45rem;font-family:var(--font-mono);font-size:.78rem;font-weight:700;min-width:36px;text-align:center;line-height:1.4}
.cd-label{font-size:.6rem;display:block;opacity:.8;text-transform:uppercase}

/* ── 11. Stats ────────────────────────────────────── */
.stats-section{background:var(--pk);padding:3rem 0}
.stat-card{text-align:center;padding:1.5rem}
.stat-icon{font-size:2.2rem;color:rgba(255,255,255,.7);margin-bottom:.6rem}
.stat-count{font-family:var(--font);font-size:2.8rem;font-weight:800;color:#fff;line-height:1;margin-bottom:.3rem}
.stat-label{color:rgba(255,255,255,.8);font-size:.9rem;font-weight:500}

/* ── 12. FAQ ──────────────────────────────────────── */
.faq-section .accordion-button{font-family:var(--font);font-weight:600;font-size:.95rem;background:var(--surface)!important;color:var(--text)!important;border:none;padding:1rem 1.2rem}
.faq-section .accordion-button:not(.collapsed){color:var(--pk)!important;box-shadow:none}
.faq-section .accordion-button::after{filter:none}
.faq-section .accordion-button:not(.collapsed)::after{filter:brightness(0) saturate(100%) invert(35%) sepia(95%) saturate(500%) hue-rotate(120deg)}
.faq-section .accordion-item{border:1px solid var(--border);border-radius:var(--r)!important;margin-bottom:.75rem;overflow:hidden;background:var(--surface)}
.faq-section .accordion-body{color:var(--muted);font-size:.9rem;line-height:1.7;padding:0 1.2rem 1rem}

/* ── 13. Newsletter ───────────────────────────────── */
.newsletter-section{background:linear-gradient(135deg,#065225,var(--pk));padding:3rem 0}
.newsletter-section h2{color:#fff;font-size:1.8rem}
.newsletter-section p{color:rgba(255,255,255,.8)}
.newsletter-input-wrap{display:flex;gap:.75rem;max-width:480px;margin:0 auto;flex-wrap:wrap}
.newsletter-input-wrap .form-control{border-radius:var(--r-sm);border:none;height:50px;font-family:var(--font);flex:1;min-width:200px}
.btn-subscribe{background:#fff;color:var(--pk);border:none;border-radius:var(--r-sm);height:50px;padding:0 1.5rem;font-weight:700;font-family:var(--font);white-space:nowrap;transition:var(--tr)}
.btn-subscribe:hover{background:var(--pk-l);transform:translateY(-2px)}

/* ── 14. Footer ───────────────────────────────────── */
.pb-footer{background:var(--g900);color:rgba(255,255,255,.75);padding:3.5rem 0 0}
.footer-brand{font-family:var(--font);font-weight:800;font-size:1.3rem;color:#fff;margin-bottom:.75rem}
.footer-brand span{color:var(--pk-m)}
.footer-about{font-size:.875rem;line-height:1.7;margin-bottom:1.2rem}
.footer-title{font-family:var(--font);font-weight:700;font-size:.9rem;color:#fff;letter-spacing:.5px;text-transform:uppercase;margin-bottom:1.1rem;padding-bottom:.5rem;border-bottom:2px solid rgba(255,255,255,.1)}
.footer-links{list-style:none;padding:0;margin:0}
.footer-links li{margin-bottom:.5rem}
.footer-links a{color:rgba(255,255,255,.65);font-size:.875rem;transition:var(--tr)}
.footer-links a:hover{color:var(--pk-m);padding-left:.3rem}
.footer-bottom{background:rgba(0,0,0,.3);margin-top:2.5rem;padding:1rem 0;text-align:center;font-size:.8rem;color:rgba(255,255,255,.5)}
.social-links{display:flex;gap:.5rem;margin-top:.75rem}
.social-links a{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.1);color:rgba(255,255,255,.7);display:flex;align-items:center;justify-content:center;font-size:1rem;transition:var(--tr)}
.social-links a:hover{background:var(--pk);color:#fff;transform:translateY(-2px)}

/* ── 15. Back to Top ─────────────────────────────── */
#back-to-top{position:fixed;bottom:1.5rem;right:1.5rem;width:44px;height:44px;background:var(--pk);color:#fff;border:none;border-radius:50%;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:var(--sh-md);opacity:0;pointer-events:none;transition:var(--tr);z-index:999}
#back-to-top.show{opacity:1;pointer-events:auto}
#back-to-top:hover{background:var(--pk-d);transform:translateY(-3px)}

/* ── 16. Draw Result Page ─────────────────────────── */
.draw-result-header{background:linear-gradient(135deg,var(--pk-d),var(--pk));color:#fff;padding:3rem 0 2rem;border-radius:0 0 var(--r-xl) var(--r-xl);margin-bottom:2rem}
.draw-info-pill{display:inline-flex;align-items:center;gap:.4rem;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:.82rem;padding:.35rem .8rem;border-radius:50px;margin:.2rem}
.prize-section{border-radius:var(--r);padding:1.5rem;margin-bottom:1.2rem;border:1.5px solid}
.prize-first{border-color:var(--gold);background:rgba(245,158,11,.06)}
.prize-second{border-color:var(--silver);background:rgba(100,116,139,.06)}
.prize-third{border-color:var(--pk);background:var(--pk-p)}
.prize-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem}
.prize-title{font-family:var(--font);font-weight:800;font-size:1.1rem}
.prize-amount-badge{font-family:var(--font);font-weight:700;font-size:.9rem;padding:.35rem .9rem;border-radius:50px}
.numbers-grid{display:flex;flex-wrap:wrap;gap:.35rem}

/* ── 17. Bond Category Page ───────────────────────── */
.bond-hero{background:linear-gradient(135deg,var(--pk-d),var(--pk));color:#fff;padding:2.5rem 0 1.5rem;border-radius:0 0 var(--r-xl) var(--r-xl);margin-bottom:2rem}
.prize-info-card{background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.2);border-radius:var(--r);padding:1rem 1.5rem;text-align:center}
.prize-info-card .pi-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.8px;opacity:.8}
.prize-info-card .pi-amount{font-size:1.2rem;font-weight:800;font-family:var(--font)}
.history-table thead{background:var(--pk);color:#fff}
.history-table tbody tr:hover{background:var(--pk-p)}

/* ── 18. Admin ────────────────────────────────────── */
.admin-layout{min-height:100vh;display:flex;flex-direction:column}
.admin-top-bar{background:var(--pk);color:#fff;padding:.75rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;position:sticky;top:0;z-index:100;box-shadow:0 2px 10px rgba(0,0,0,.15)}
.admin-top-bar .brand{font-family:var(--font);font-weight:800;font-size:1.1rem;color:#fff}
.admin-nav-tabs{background:var(--surface);border-bottom:2px solid var(--border);padding:.75rem 1.5rem;display:flex;gap:.5rem;flex-wrap:wrap;overflow-x:auto}
.admin-tab{background:none;border:none;color:var(--muted);font-family:var(--font);font-weight:600;font-size:.875rem;padding:.5rem 1.1rem;border-radius:var(--r-sm);cursor:pointer;transition:var(--tr)}
.admin-tab:hover{background:var(--pk-l);color:var(--pk)}
.admin-tab.active{background:var(--pk);color:#fff}
.admin-body{flex:1;padding:1.5rem;background:var(--bg)}
.admin-card{background:var(--surface);border-radius:var(--r);box-shadow:var(--sh);border:1px solid var(--border);margin-bottom:1.2rem;overflow:hidden}
.admin-card-header{padding:1rem 1.5rem;border-bottom:1px solid var(--border);font-family:var(--font);font-weight:700;font-size:.95rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem}
.admin-card-body{padding:1.5rem}
.btn-pk{background:var(--pk);color:#fff;border:none;border-radius:var(--r-sm);padding:.5rem 1.2rem;font-weight:600;font-family:var(--font);transition:var(--tr);cursor:pointer}
.btn-pk:hover{background:var(--pk-d);color:#fff;transform:translateY(-1px)}
.btn-pk:disabled{opacity:.6;cursor:not-allowed;transform:none}
.admin-stat{background:linear-gradient(135deg,var(--pk),var(--pk-m));color:#fff;border-radius:var(--r);padding:1.2rem;text-align:center}
.admin-stat-val{font-size:2rem;font-weight:800;font-family:var(--font);line-height:1}
.admin-stat-label{font-size:.8rem;opacity:.8;margin-top:.3rem}
.admin-table thead{background:var(--g100)}
.admin-table thead th{color:var(--muted);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;border:none;padding:.75rem 1rem}
.admin-table tbody td{vertical-align:middle;font-size:.875rem;padding:.85rem 1rem;border-color:var(--border)}
.admin-table tbody tr:hover{background:var(--pk-p)}
.form-label{font-weight:600;font-size:.875rem;color:var(--text)}
.form-control,.form-select{border-color:var(--border);background:var(--surface);color:var(--text);border-radius:var(--r-sm)}
.form-control:focus,.form-select:focus{border-color:var(--pk);box-shadow:0 0 0 3px rgba(11,143,58,.15);background:var(--surface);color:var(--text)}
.winners-area{font-family:var(--font-mono);font-size:.82rem;border-color:var(--border)}
.prize-section-input{border-radius:var(--r);padding:1.2rem;border:1.5px solid}
.prize-first-input{border-color:rgba(245,158,11,.4);background:rgba(245,158,11,.04)}
.prize-second-input{border-color:rgba(100,116,139,.3);background:rgba(100,116,139,.04)}
.prize-third-input{border-color:rgba(11,143,58,.3);background:var(--pk-p)}
.login-card{max-width:380px;margin:4rem auto;background:var(--surface);border-radius:var(--r-lg);padding:2.5rem;box-shadow:var(--sh-lg);border:1px solid var(--border)}
.login-logo{font-family:var(--font);font-size:1.8rem;font-weight:800;color:var(--pk);text-align:center;margin-bottom:1.5rem}

/* ── 19. Utilities ────────────────────────────────── */
.badge-premium{background:rgba(245,158,11,.15);color:#B45309;font-size:.7rem;font-weight:700;padding:.25rem .6rem;border-radius:50px;border:1px solid rgba(245,158,11,.3)}
.breadcrumb-wrap{background:var(--pk-p);border-bottom:1px solid var(--border);padding:.65rem 0}
.breadcrumb{margin:0;font-size:.83rem}
.breadcrumb-item+.breadcrumb-item::before{color:var(--muted)}
.breadcrumb-item.active{color:var(--pk);font-weight:600}
.no-results{text-align:center;padding:3rem;color:var(--muted)}
.no-results .icon{font-size:3rem;margin-bottom:1rem;display:block;opacity:.4}
.spinner-pk{width:2rem;height:2rem;border:.3rem solid var(--pk-l);border-top-color:var(--pk);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.loading-wrap{display:flex;justify-content:center;padding:2rem}
.alert-pk{background:var(--pk-l);border-color:var(--pk);color:var(--pk-d)}

/* ── 20. Responsive ───────────────────────────────── */
@media(max-width:768px){
    .pb-hero{padding:3rem 0 2.5rem}
    .hero-search-box{padding:1rem;margin:0 .5rem 1.5rem}
    .hero-search-box .row>div{margin-bottom:.5rem}
    .pb-section{padding:2.5rem 0}
    .stat-count{font-size:2.2rem}
    .bond-hero .row>div{margin-bottom:.75rem}
    .admin-body{padding:1rem}
}
@media(max-width:576px){
    .hero-title{font-size:1.6rem}
    .number-chip{font-size:.78rem;padding:.25rem .5rem}
    .countdown-box{gap:.2rem}
}

/* ── 21. Print ────────────────────────────────────── */
@media print{
    .pb-nav,.pb-footer,#back-to-top,.btn,.no-print{display:none!important}
    .pb-section{padding:1rem 0}
    body{color:#000}
}
</style>
</head>
<body>

<?php if($db_error): ?>
<div style="font-family:sans-serif;padding:2rem;max-width:600px;margin:3rem auto;background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;color:#991b1b">
  <h2 style="margin-bottom:.5rem">⚠️ Database Connection Error</h2>
  <p style="margin-bottom:1rem">Could not connect to the database. Please check your configuration at the top of this file.</p>
  <code style="background:#fee2e2;padding:.25rem .5rem;border-radius:4px;font-size:.85rem"><?=e($db_error)?></code>
  <p style="margin-top:1rem;font-size:.85rem">Edit <strong>DB_HOST</strong>, <strong>DB_NAME</strong>, <strong>DB_USER</strong>, <strong>DB_PASS</strong> in the CONFIG section.</p>
</div>
<?php exit; endif; ?>

<?php
// ════════════════════════════════════════════════════════════
// ■  ADMIN PAGES (completely separate layout)
// ════════════════════════════════════════════════════════════
if($page==='admin'):
    if(!$is_admin):
?>
<div style="background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem">
  <div class="login-card">
    <div class="login-logo"><i class="bi bi-award-fill"></i> Prize Bond PK</div>
    <h5 class="text-center mb-1" style="font-family:var(--font);font-weight:700">Admin Login</h5>
    <p class="text-center text-muted mb-3" style="font-size:.875rem">Enter admin password to continue</p>
    <?php if($adm_msg):?><div class="alert alert-danger py-2 mb-3 text-center" style="font-size:.875rem"><?=e($adm_msg)?></div><?php endif;?>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="pass" class="form-control" autofocus required placeholder="Enter admin password">
      </div>
      <button type="submit" name="login_submit" class="btn-pk w-100 py-2" style="border-radius:var(--r-sm);font-size:1rem"><i class="bi bi-shield-lock me-1"></i> Enter Admin Panel</button>
    </form>
    <div class="text-center mt-3"><a href="<?=sp(['page'=>'home'])?>" style="font-size:.83rem;color:var(--muted)">← Back to Website</a></div>
  </div>
</div>
<script>document.documentElement.setAttribute('data-theme',localStorage.getItem('pb-theme')||'light')</script>
<?php else: ?>
<div class="admin-layout">
  <!-- Admin Top Bar -->
  <div class="admin-top-bar">
    <div class="brand"><i class="bi bi-award-fill me-2"></i><?=SITE_NAME?> <span style="opacity:.6;font-size:.8rem;font-weight:400">Admin</span></div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <?php if($adm_msg):?><span class="alert alert-<?=$adm_type?> py-1 px-3 mb-0 text-sm" style="font-size:.8rem"><?=e($adm_msg)?></span><?php endif;?>
      <a href="<?=sp(['page'=>'home'])?>" class="btn btn-sm btn-outline-light"><i class="bi bi-house me-1"></i>View Site</a>
      <a href="<?=sp(['page'=>'admin','logout'=>'1'])?>" class="btn btn-sm btn-light text-pk fw-semibold">Logout</a>
    </div>
  </div>

  <!-- Admin Nav Tabs -->
  <div class="admin-nav-tabs">
    <?php foreach([['draws','All Draws','bi-list-ul'],['add','Add Draw','bi-plus-circle'],['schedule','Schedule','bi-calendar3'],['subscribers','Subscribers','bi-envelope']]as[$sub,$label,$icon]):?>
      <a href="<?=sp(['page'=>'admin','sub'=>$sub])?>" class="admin-tab <?=$adm_sub===$sub?'active':''?>">
        <i class="<?=$icon?> me-1"></i><?=$label?>
      </a>
    <?php endforeach;?>
  </div>

  <div class="admin-body">
  <?php
  // ── Admin: All Draws ─────────────────────────────────────────
  if($adm_sub==='draws'):
      $adm_pg=max(1,(int)($_GET['pg']??1));$adm_pp=20;
      $all_draws=db_all_draws($adm_pp,($adm_pg-1)*$adm_pp);
      $total_d=(int)$pdo->query("SELECT COUNT(*) FROM pb_draws")->fetchColumn();
      $st=$stats;
  ?>
  <!-- Stats row -->
  <div class="row g-3 mb-4">
    <?php foreach([['Total Draws',$st['draws'],'bi-collection'],['Winner Numbers',$st['winners'],'bi-trophy'],['Bond Types',$st['types'],'bi-layers'],['Searches',$st['searches'],'bi-search']]as[$lbl,$val,$ico]):?>
    <div class="col-6 col-md-3">
      <div class="admin-stat"><div class="mb-1" style="font-size:1.5rem"><i class="<?=$ico?>"></i></div><div class="admin-stat-val"><?=number_format($val)?></div><div class="admin-stat-label"><?=$lbl?></div></div>
    </div>
    <?php endforeach;?>
  </div>

  <div class="admin-card">
    <div class="admin-card-header">
      All Draw Results (<?=number_format($total_d)?>)
      <a href="<?=sp(['page'=>'admin','sub'=>'add'])?>" class="btn-pk" style="font-size:.82rem;padding:.4rem .9rem"><i class="bi bi-plus-lg me-1"></i>Add New Draw</a>
    </div>
    <div style="overflow-x:auto">
      <table class="table admin-table mb-0">
        <thead><tr><th>ID</th><th>Bond Type</th><th>Draw #</th><th>Date</th><th>City</th><th>Winners</th><th>Status</th><th>PDF</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if(!$all_draws):?><tr><td colspan="9" class="no-results"><span class="icon">📋</span>No draws yet. <a href="<?=sp(['page'=>'admin','sub'=>'add'])?>">Add the first draw</a>.</td></tr><?php endif;?>
          <?php foreach($all_draws as $d):?>
          <tr>
            <td class="text-muted" style="font-size:.78rem"><?=$d->id?></td>
            <td><span class="badge" style="background:var(--pk);font-size:.75rem">Rs.<?=number_format($d->denomination)?></span></td>
            <td><strong>#<?=e($d->draw_number)?></strong></td>
            <td><?=date('d M Y',strtotime($d->draw_date))?></td>
            <td><?=e($d->city)?></td>
            <td><span class="badge bg-light text-dark border" style="font-size:.75rem"><?=number_format($d->total_winners??0)?></span></td>
            <td><?=$d->status==='published'?'<span class="badge bg-success">Live</span>':'<span class="badge bg-secondary">Draft</span>'?></td>
            <td><?=$d->pdf_url?'<a href="'.e($d->pdf_url).'" target="_blank" style="font-size:.78rem;color:var(--danger)"><i class="bi bi-file-earmark-pdf"></i> PDF</a>':'<span class="text-muted">–</span>'?></td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <a href="<?=sp(['page'=>'admin','sub'=>'add','draw_id'=>$d->id])?>" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;padding:.25rem .6rem">Edit</a>
                <a href="<?=sp(['page'=>'admin','del_draw'=>$d->id])?>" class="btn btn-sm btn-outline-danger" style="font-size:.75rem;padding:.25rem .6rem" onclick="return confirm('Delete Draw #<?=e($d->draw_number)?>? All winner numbers will be lost.')">Delete</a>
              </div>
            </td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </div>

  <?php // Pagination
  $total_adm_pages=ceil($total_d/$adm_pp);
  if($total_adm_pages>1):?>
  <nav><ul class="pagination pagination-sm flex-wrap gap-1"><?php for($i=1;$i<=$total_adm_pages;$i++):?><li class="page-item <?=$i==$adm_pg?'active':''?>"><a class="page-link" href="<?=sp(['page'=>'admin','sub'=>'draws','pg'=>$i])?>"><?=$i?></a></li><?php endfor;?></ul></nav>
  <?php endif;?>

  <?php elseif($adm_sub==='add'):
      $edit_id=(int)($_GET['draw_id']??0);
      $edit_draw=$edit_id?db_draw($edit_id):null;
      $edit_winners=$edit_id?db_winners($edit_id):[];
      $grouped=['first'=>[],'second'=>[],'third'=>[]];
      foreach($edit_winners as $w)$grouped[$w->prize_type][]=$w->winning_number;
      if(empty($_SESSION['adm_token']))$_SESSION['adm_token']=bin2hex(random_bytes(16));
  ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <form method="POST">
        <input type="hidden" name="_tok" value="<?=e($_SESSION['adm_token'])?>">
        <?php if($edit_id):?><input type="hidden" name="draw_id" value="<?=$edit_id?>"><?php endif;?>

        <div class="admin-card">
          <div class="admin-card-header"><?=$edit_draw?'Edit Draw #'.e($edit_draw->draw_number):'Add New Draw Result'?></div>
          <div class="admin-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Bond Type <span class="text-danger">*</span></label>
                <select name="bond_type_id" class="form-select" required>
                  <option value="">Select Bond Type</option>
                  <?php foreach($bond_types as $bt):?>
                    <option value="<?=$bt->id?>" <?=($edit_draw&&$edit_draw->bond_type_id==$bt->id)?'selected':'';?>>
                      <?=e($bt->name)?><?=$bt->is_premium?' ⭐':''?>
                    </option>
                  <?php endforeach;?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Draw Number <span class="text-danger">*</span></label>
                <input type="text" name="draw_number" class="form-control mono" placeholder="e.g. 97" value="<?=e($edit_draw->draw_number??'')?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Draw Date <span class="text-danger">*</span></label>
                <input type="date" name="draw_date" class="form-control" value="<?=e($edit_draw->draw_date??'')?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">City <span class="text-danger">*</span></label>
                <select name="city" class="form-select" required>
                  <option value="">Select City</option>
                  <?php foreach(cities() as $c):?>
                    <option value="<?=e($c)?>" <?=($edit_draw&&$edit_draw->city===$c)?'selected':''?>><?=e($c)?></option>
                  <?php endforeach;?>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label">Official SBP PDF URL</label>
                <input type="url" name="pdf_url" class="form-control" placeholder="https://sbp.org.pk/..." value="<?=e($edit_draw->pdf_url??'')?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                  <option value="published" <?=(!$edit_draw||$edit_draw->status==='published')?'selected':''?>>Published (Live)</option>
                  <option value="draft" <?=($edit_draw&&$edit_draw->status==='draft')?'selected':''?>>Draft (Hidden)</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Winning Numbers -->
        <div class="admin-card">
          <div class="admin-card-header">Enter Winning Numbers <small class="text-muted fw-normal">(one per line, or comma/space separated)</small></div>
          <div class="admin-card-body">
            <?php foreach([['first','🥇 First Prize (1 number)','prize-first-input'],['second','🥈 Second Prize (3 numbers)','prize-second-input'],['third','🥉 Third Prize (~1696 numbers)','prize-third-input']] as [$pt,$label,$cls]):?>
            <div class="prize-section-input <?=$cls?> mb-3">
              <label class="form-label"><?=$label?></label>
              <textarea name="winners_<?=$pt?>" class="form-control winners-area" rows="<?=$pt==='third'?10:3?>" placeholder="Paste numbers here..."><?=e(implode("\n",$grouped[$pt]))?></textarea>
            </div>
            <?php endforeach;?>

            <div class="mt-3 p-3 rounded" style="background:var(--g100);border:1px solid var(--border)">
              <h6 class="mb-2" style="font-size:.875rem"><i class="bi bi-file-earmark-spreadsheet me-1 text-pk"></i>Import from CSV / Excel File</h6>
              <input type="file" id="import-file" class="form-control form-control-sm mb-2" accept=".csv,.xlsx,.xls" style="max-width:360px">
              <button type="button" onclick="parseImportFile()" class="btn btn-sm btn-outline-secondary">Parse & Fill Numbers</button>
              <div id="parse-msg" class="mt-2" style="font-size:.8rem"></div>
              <small class="text-muted d-block mt-1">CSV format: prize_type,winning_number (e.g. "third,123456"). Or just a column of numbers (all treated as third prize).</small>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" name="save_draw" class="btn-pk px-4 py-2" style="font-size:1rem;border-radius:var(--r-sm)">
            <i class="bi bi-check-lg me-1"></i><?=$edit_draw?'Update Draw':'Save Draw'?>
          </button>
          <a href="<?=sp(['page'=>'admin','sub'=>'draws'])?>" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
        </div>
      </form>
    </div>

    <?php if($edit_draw):?>
    <div class="col-lg-4">
      <div class="admin-card">
        <div class="admin-card-header">Draw Summary</div>
        <div class="admin-card-body">
          <div class="admin-stat mb-3"><div class="admin-stat-val"><?=number_format($edit_draw->total_winners??0)?></div><div class="admin-stat-label">Total Winners</div></div>
          <table class="table table-sm mb-0">
            <tr><td>1st Prize</td><td class="text-end fw-bold text-warning"><?=count($grouped['first'])?></td></tr>
            <tr><td>2nd Prize</td><td class="text-end fw-bold text-secondary"><?=count($grouped['second'])?></td></tr>
            <tr><td>3rd Prize</td><td class="text-end fw-bold text-pk"><?=count($grouped['third'])?></td></tr>
            <tr><td class="text-muted">Page Views</td><td class="text-end text-muted"><?=number_format($edit_draw->views??0)?></td></tr>
          </table>
          <a href="<?=sp(['page'=>'draw','id'=>$edit_id])?>" class="btn btn-sm btn-outline-success w-100 mt-3" target="_blank"><i class="bi bi-eye me-1"></i>View Public Page</a>
        </div>
      </div>
    </div>
    <?php endif;?>
  </div>

  <?php elseif($adm_sub==='schedule'):?>
  <div class="row g-4">
    <div class="col-lg-5">
      <div class="admin-card">
        <div class="admin-card-header">Add Upcoming Draw</div>
        <div class="admin-card-body">
          <form method="POST">
            <div class="mb-3"><label class="form-label">Bond Type *</label>
              <select name="bond_type_id" class="form-select" required><option value="">Select...</option><?php foreach($bond_types as $bt):?><option value="<?=$bt->id?>"><?=e($bt->name)?></option><?php endforeach;?></select>
            </div>
            <div class="mb-3"><label class="form-label">Draw Number *</label><input type="text" name="draw_number" class="form-control mono" placeholder="e.g. 98" required></div>
            <div class="mb-3"><label class="form-label">Draw Date *</label><input type="date" name="draw_date" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">City *</label>
              <select name="city" class="form-select" required><option value="">Select City</option><?php foreach(cities() as $c):?><option><?=e($c)?></option><?php endforeach;?></select>
            </div>
            <div class="mb-3"><label class="form-label">Venue</label><input type="text" name="venue" class="form-control" placeholder="National Savings Centre…"></div>
            <button type="submit" name="save_schedule" class="btn-pk w-100 py-2" style="border-radius:var(--r-sm)"><i class="bi bi-plus-lg me-1"></i>Add to Schedule</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="admin-card">
        <div class="admin-card-header">Upcoming Draws (<?=count(db_schedules('upcoming',100))?>)</div>
        <div style="overflow-x:auto"><table class="table admin-table mb-0">
          <thead><tr><th>Bond</th><th>Draw #</th><th>Date</th><th>City</th><th>Status</th></tr></thead>
          <tbody>
            <?php $upcomings=db_schedules('upcoming',50);if(!$upcomings):?><tr><td colspan="5" class="text-center py-4 text-muted">No upcoming draws scheduled.</td></tr><?php endif;?>
            <?php foreach($upcomings as $s):?>
            <tr>
              <td><span class="badge" style="background:var(--pk)">Rs.<?=number_format($s->denomination)?></span></td>
              <td class="mono">#<?=e($s->draw_number)?></td>
              <td><?=date('d M Y',strtotime($s->draw_date))?></td>
              <td><?=e($s->city)?></td>
              <td><span class="badge bg-info text-dark">Upcoming</span></td>
            </tr>
            <?php endforeach;?>
          </tbody>
        </table></div>
      </div>
    </div>
  </div>

  <?php elseif($adm_sub==='subscribers'):
      $subs=$pdo->query("SELECT*FROM pb_subscribers WHERE is_active=1 ORDER BY subscribed_at DESC LIMIT 200")->fetchAll();
  ?>
  <div class="admin-card">
    <div class="admin-card-header">Newsletter Subscribers (<?=count($subs)?>)
      <a href="#" class="btn btn-sm btn-outline-secondary" onclick="exportSubscribers()"><i class="bi bi-download me-1"></i>Export CSV</a>
    </div>
    <div style="overflow-x:auto"><table class="table admin-table mb-0">
      <thead><tr><th>Email</th><th>Name</th><th>Subscribed</th><th>Status</th></tr></thead>
      <tbody>
        <?php if(!$subs):?><tr><td colspan="4" class="text-center py-4 text-muted">No subscribers yet.</td></tr><?php endif;?>
        <?php foreach($subs as $s):?>
        <tr><td><?=e($s->email)?></td><td><?=e($s->name??'—')?></td><td><?=date('d M Y',strtotime($s->subscribed_at))?></td><td><span class="badge bg-success">Active</span></td></tr>
        <?php endforeach;?>
      </tbody>
    </table></div>
  </div>
  <?php endif;?>
  </div><!-- /admin-body -->
</div><!-- /admin-layout -->
<?php endif; // end admin
else: // ── PUBLIC SITE PAGES ─────────────────────────────────────────────── ?>

<!-- ════ PUBLIC NAVBAR ════ -->
<nav class="pb-nav navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?=sp(['page'=>'home'])?>">
      <i class="bi bi-award-fill me-1"></i>Prize Bond<span>PK</span>
    </a>
    <div class="d-flex align-items-center gap-2 ms-auto d-lg-none">
      <button class="dark-btn" onclick="toggleDark()" title="Toggle dark mode"><i class="bi bi-moon-fill" id="dark-icon-m"></i></button>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu"><span class="navbar-toggler-icon"></span></button>
    </div>
    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav me-auto ms-3">
        <li class="nav-item"><a class="nav-link <?=$page==='home'?'active':''?>" href="<?=sp(['page'=>'home'])?>"><i class="bi bi-house me-1"></i>Home</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-layers me-1"></i>Bond Types
          </a>
          <ul class="dropdown-menu">
            <?php foreach($bond_types as $bt):?>
            <li><a class="dropdown-item" href="<?=sp(['page'=>'bond','type'=>$bt->slug])?>">
              <?=e($bt->name)?> <?=$bt->is_premium?'⭐':''?>
            </a></li>
            <?php endforeach;?>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link <?=$page==='search'?'active':''?>" href="<?=sp(['page'=>'search'])?>"><i class="bi bi-search me-1"></i>Check Bond</a></li>
        <li class="nav-item"><a class="nav-link <?=$page==='schedule'?'active':''?>" href="<?=sp(['page'=>'schedule'])?>"><i class="bi bi-calendar3 me-1"></i>Schedule</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots me-1"></i>More</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?=sp(['page'=>'about'])?>"><i class="bi bi-info-circle me-2"></i>About Us</a></li>
            <li><a class="dropdown-item" href="<?=sp(['page'=>'contact'])?>"><i class="bi bi-envelope me-2"></i>Contact Us</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?=sp(['page'=>'privacy'])?>">Privacy Policy</a></li>
            <li><a class="dropdown-item" href="<?=sp(['page'=>'terms'])?>">Terms &amp; Conditions</a></li>
          </ul>
        </li>
      </ul>
      <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
        <a href="<?=sp(['page'=>'search'])?>" class="nav-link nav-btn"><i class="bi bi-search me-1"></i>Check Number</a>
        <button class="dark-btn d-none d-lg-block" onclick="toggleDark()" title="Toggle dark mode"><i class="bi bi-moon-fill" id="dark-icon"></i></button>
      </div>
    </div>
  </div>
</nav>
<!-- /NAVBAR -->

<?php
// ════════════════════════════════════════════════════════════
// ■  HOMEPAGE
// ════════════════════════════════════════════════════════════
if($page==='home'):
?>
<!-- HERO -->
<section class="pb-hero">
  <div class="container text-center">
    <span class="hero-badge"><i class="bi bi-lightning-fill me-1"></i> Live Draw Results — Pakistan's #1 Source</span>
    <h1 class="hero-title">Pakistan Prize Bond<br>Result Check Online</h1>
    <p class="hero-sub">Instantly search all denominations — Rs.100 to Rs.40,000. Official draw data, real-time results.</p>

    <div class="hero-search-box">
      <div class="row g-2 align-items-center">
        <div class="col-lg-4 col-md-4">
          <select id="hero-bond-type" class="form-select"><option value="">All Bond Types</option><?php foreach($bond_types as $bt):?><option value="<?=$bt->id?>"><?=e($bt->name)?></option><?php endforeach;?></select>
        </div>
        <div class="col-lg-5 col-md-5">
          <input type="text" id="hero-number" class="form-control mono" placeholder="Enter your bond number…" maxlength="10">
        </div>
        <div class="col-lg-3 col-md-3">
          <button class="btn-hero-search w-100" onclick="heroSearch()"><i class="bi bi-search me-1"></i>Search</button>
        </div>
      </div>
      <div id="hero-result" class="mt-3 text-start" style="display:none"></div>
    </div>

    <div class="quick-types">
      <?php foreach($bond_types as $bt):?>
      <a href="<?=sp(['page'=>'bond','type'=>$bt->slug])?>" class="quick-type-btn">Rs.<?=number_format($bt->denomination)?><?=$bt->is_premium?' ⭐':''?></a>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- LATEST DRAW RESULTS -->
<section class="pb-section">
  <div class="container">
    <div class="row align-items-end mb-4">
      <div class="col">
        <div class="section-eyebrow">Draw Results</div>
        <h2 class="section-title mb-0">Latest Prize Bond Results</h2>
      </div>
      <div class="col-auto"><a href="<?=sp(['page'=>'schedule'])?>" class="btn btn-outline-success btn-sm rounded-pill"><i class="bi bi-calendar3 me-1"></i>View Schedule</a></div>
    </div>

    <?php if(!$latest_draws):?>
    <div class="no-results"><span class="icon">📋</span><p class="mb-2">No draw results yet.</p><a href="<?=sp(['page'=>'admin','sub'=>'add'])?>" class="btn-pk px-4 py-2" style="border-radius:var(--r-sm);display:inline-block">Add First Draw →</a></div>
    <?php else:?>
    <div class="row g-3">
      <?php foreach($latest_draws as $d):?>
      <div class="col-lg-4 col-md-6">
        <div class="pb-card draw-card p-3 <?=$d->is_premium?'premium':''?>">
          <div class="badge-denom">Rs.<?=number_format($d->denomination)?><?=$d->is_premium?' ⭐':''?></div>
          <div class="meta"><i class="bi bi-calendar3 me-1"></i><?=date('d M Y',strtotime($d->draw_date))?> &nbsp;|&nbsp; <i class="bi bi-geo-alt me-1"></i><?=e($d->city)?></div>

          <?php $first_winners=db_winners($d->id,'first');?>
          <div class="draw-num"><?=$first_winners?e($first_winners[0]->winning_number):'—'?></div>
          <div class="prize-label mt-1">First Prize Winner</div>

          <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
            <div>
              <div class="prize-label">First Prize</div>
              <div class="prize-val"><?=fmt((int)$d->first_prize_amount)?></div>
            </div>
            <a href="<?=sp(['page'=>'draw','id'=>$d->id])?>" class="btn btn-outline-success btn-view">Full Result <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
      </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>
  </div>
</section>

<!-- BOND CATEGORIES -->
<section class="pb-section" style="background:var(--g100);padding-top:3rem">
  <div class="container">
    <div class="section-eyebrow">All Denominations</div>
    <h2 class="section-title">Prize Bond Categories</h2>
    <p class="section-sub">From Rs.100 to Rs.40,000 — choose your bond type to see the latest result and complete winner list.</p>

    <?php
    $icons=[100=>'💯',200=>'💚',750=>'🎯',1500=>'🌟',3000=>'💎',7500=>'🏅',15000=>'🥇',25000=>'👑',40000=>'🏆'];
    ?>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-3">
      <?php foreach($bond_types as $bt):?>
      <div class="col">
        <a href="<?=sp(['page'=>'bond','type'=>$bt->slug])?>" class="bond-cat-card d-block" style="color:var(--text)">
          <?php if($bt->is_premium):?><span class="premium-badge">PREMIUM</span><?php endif;?>
          <div class="icon-wrap" style="background:var(--pk-l);color:var(--pk)"><?=$icons[$bt->denomination]??'🎫'?></div>
          <div class="cat-name">Rs.<?=number_format($bt->denomination)?></div>
          <div class="cat-prize">1st Prize: <strong><?=fmt((int)$bt->first_prize_amount)?></strong></div>
          <div class="cat-prize"><?=$bt->draws_per_year?> draws/year</div>
        </a>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- SEARCH SECTION -->
<section class="pb-section search-section-bg">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="text-center mb-4">
          <div class="section-eyebrow">Instant Checker</div>
          <h2 class="section-title">Check Your Bond Number</h2>
          <p class="section-sub mx-auto">Enter your bond number below to instantly see if you've won in the latest draw.</p>
        </div>
        <div class="pb-card p-4">
          <ul class="nav search-tabs mb-3" id="searchTabs">
            <li class="nav-item"><button class="nav-link active" data-tab="single">Single Number</button></li>
            <li class="nav-item"><button class="nav-link" data-tab="bulk">Multiple Numbers</button></li>
          </ul>

          <!-- Single Search -->
          <div id="tab-single">
            <div class="row g-2 mb-3">
              <div class="col-md-4"><select id="s-bond-type" class="form-select"><option value="">All Bond Types</option><?php foreach($bond_types as $bt):?><option value="<?=$bt->id?>"><?=e($bt->name)?></option><?php endforeach;?></select></div>
              <div class="col-md-4"><select id="s-draw-id" class="form-select"><option value="">Latest Draw</option></select></div>
              <div class="col-md-4 d-flex gap-2"><input type="text" id="s-number" class="form-control mono flex-grow-1" placeholder="Bond number…" maxlength="10"><button class="btn-pk px-3" onclick="doSearch()" style="border-radius:var(--r-sm);min-width:48px"><i class="bi bi-search"></i></button></div>
            </div>
            <div id="s-result"></div>
          </div>

          <!-- Bulk Search -->
          <div id="tab-bulk" style="display:none">
            <div class="row g-2 mb-3">
              <div class="col-md-4"><select id="b-bond-type" class="form-select"><option value="">All Bond Types</option><?php foreach($bond_types as $bt):?><option value="<?=$bt->id?>"><?=e($bt->name)?></option><?php endforeach;?></select></div>
              <div class="col-md-4"><select id="b-draw-id" class="form-select"><option value="">Latest Draw</option></select></div>
            </div>
            <textarea id="b-numbers" class="form-control mono mb-2" rows="6" placeholder="Paste up to 500 bond numbers, one per line or comma separated..."></textarea>
            <div class="d-flex gap-2 align-items-center flex-wrap">
              <button class="btn-pk px-4 py-2" onclick="doBulk()" style="border-radius:var(--r-sm)"><i class="bi bi-search me-1"></i>Check All Numbers</button>
              <input type="file" id="bulk-file" class="form-control form-control-sm" accept=".csv,.txt,.xlsx" style="max-width:220px">
              <button class="btn btn-sm btn-outline-secondary" onclick="loadBulkFile()"><i class="bi bi-upload me-1"></i>Load File</button>
            </div>
            <div id="b-result" class="mt-3"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- UPCOMING SCHEDULE -->
<?php if($schedules):?>
<section class="pb-section">
  <div class="container">
    <div class="row align-items-end mb-4">
      <div class="col">
        <div class="section-eyebrow">Coming Soon</div>
        <h2 class="section-title mb-0">Upcoming Draw Schedule</h2>
      </div>
      <div class="col-auto"><a href="<?=sp(['page'=>'schedule'])?>" class="btn btn-outline-success btn-sm rounded-pill">Full Schedule <i class="bi bi-arrow-right"></i></a></div>
    </div>
    <div class="schedule-table">
      <table class="table mb-0 w-100">
        <thead><tr><th>Bond Type</th><th>Draw #</th><th>Date</th><th>City</th><th>Countdown</th></tr></thead>
        <tbody>
          <?php foreach($schedules as $sch):?>
          <tr>
            <td><span class="badge me-1" style="background:var(--pk)">Rs.<?=number_format($sch->denomination)?></span><?=$sch->is_premium?'<span class="badge-premium">Premium</span>':''?></td>
            <td class="mono fw-bold">#<?=e($sch->draw_number)?></td>
            <td><i class="bi bi-calendar3 me-1 text-pk"></i><?=date('d M Y',strtotime($sch->draw_date))?></td>
            <td><i class="bi bi-geo-alt me-1"></i><?=e($sch->city)?></td>
            <td><div class="countdown-box" data-date="<?=e($sch->draw_date)?>">
              <span class="cd-unit"><span class="cd-days">—</span><small class="cd-label">days</small></span>
              <span class="cd-unit"><span class="cd-hours">—</span><small class="cd-label">hrs</small></span>
              <span class="cd-unit"><span class="cd-mins">—</span><small class="cd-label">min</small></span>
            </div></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php endif;?>

<!-- STATS -->
<section class="stats-section">
  <div class="container">
    <div class="row text-center">
      <?php foreach([['Total Draws',$stats['draws'],'bi-collection'],['Winner Numbers',$stats['winners'],'bi-trophy-fill'],['Bond Types',$stats['types'],'bi-layers-half'],['Searches Done',$stats['searches'],'bi-search']]as[$lbl,$val,$ico]):?>
      <div class="col-6 col-md-3 stat-card">
        <div class="stat-icon"><i class="<?=$ico?>"></i></div>
        <div class="stat-count counter" data-target="<?=$val?>"><?=number_format($val)?></div>
        <div class="stat-label"><?=$lbl?></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="pb-section faq-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="text-center mb-4">
          <div class="section-eyebrow">FAQ</div>
          <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div class="accordion" id="faqAccordion">
          <?php foreach([
            ['What is a Prize Bond in Pakistan?','A Prize Bond is a bearer-type security issued by the Government of Pakistan through the State Bank of Pakistan (SBP). Instead of earning fixed interest, holders enter periodic prize draws where they can win cash prizes from Rs.1,000 up to Rs.80 million.'],
            ['How often are Prize Bond draws held?','Most draws are held quarterly (4 times/year) on the 15th of January, April, July, and October. Premium bonds (Rs.25,000 and Rs.40,000) have semi-annual draws (2 times/year).'],
            ['How do I check if my bond number has won?','Enter your bond number in the search box above, select your bond denomination and draw, then click Search. We show instant results directly from official SBP draw data.'],
            ['How do I claim my prize?','Winners must claim their prize within 6 years from the draw date at any State Bank of Pakistan or authorized National Savings branch. Bring your original bond certificate and a valid CNIC.'],
            ['Are prize bond winnings taxable?','Yes. Winnings are subject to withholding tax in Pakistan. Tax filers pay a lower rate than non-filers. Consult FBR guidelines or a tax advisor for current rates.'],
            ['Where can I buy prize bonds?','You can purchase prize bonds from any State Bank of Pakistan branch, National Savings Centre, or authorized commercial bank branch across Pakistan.'],
          ] as $idx=>[$q,$a]):?>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button <?=$idx>0?'collapsed':''?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?=$idx?>">
                <?=e($q)?>
              </button>
            </h2>
            <div id="faq<?=$idx?>" class="accordion-collapse collapse <?=$idx===0?'show':''?>" data-bs-parent="#faqAccordion">
              <div class="accordion-body"><?=e($a)?></div>
            </div>
          </div>
          <?php endforeach;?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- NEWSLETTER -->
<section class="newsletter-section">
  <div class="container text-center">
    <i class="bi bi-bell-fill" style="font-size:2rem;color:rgba(255,255,255,.7);margin-bottom:.75rem;display:block"></i>
    <h2 class="mb-2">Get Draw Results by Email</h2>
    <p class="mb-3">Be the first to know when new draw results are published.</p>
    <div class="newsletter-input-wrap">
      <input type="text" id="nl-name" class="form-control" placeholder="Your name (optional)">
      <input type="email" id="nl-email" class="form-control" placeholder="your@email.com" required>
      <button class="btn-subscribe" onclick="doSubscribe()"><i class="bi bi-send me-1"></i>Subscribe</button>
    </div>
    <div id="nl-msg" class="mt-3" style="font-size:.9rem;color:rgba(255,255,255,.9)"></div>
  </div>
</section>

<?php
// ════════════════════════════════════════════════════════════
// ■  BOND CATEGORY PAGE
// ════════════════════════════════════════════════════════════
elseif($page==='bond'&&$cur_bond):
    $total_bd=db_count_draws($cur_bond->id);
    $total_bd_pages=ceil($total_bd/$bpp);
?>
<!-- Breadcrumb -->
<div class="breadcrumb-wrap"><div class="container"><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="<?=sp(['page'=>'home'])?>">Home</a></li><li class="breadcrumb-item active"><?=e($cur_bond->name)?></li></ol></nav></div></div>

<!-- Bond Hero -->
<div class="bond-hero">
  <div class="container">
    <div class="d-flex align-items-center gap-3 mb-3">
      <h1 class="mb-0 text-white" style="font-size:1.8rem"><?=e($cur_bond->name)?></h1>
      <?php if($cur_bond->is_premium):?><span class="badge-premium" style="position:static;font-size:.8rem">PREMIUM</span><?php endif;?>
    </div>
    <div class="row g-3">
      <?php foreach([['1st Prize',fmt((int)$cur_bond->first_prize_amount),'🥇'],['2nd Prize',fmt((int)$cur_bond->second_prize_amount).' × '.$cur_bond->second_prize_count,'🥈'],['3rd Prize',fmt((int)$cur_bond->third_prize_amount).' × '.$cur_bond->third_prize_count,'🥉'],['Draws/Year',$cur_bond->draws_per_year.' times','📅']]as[$lbl,$val,$icon]):?>
      <div class="col-6 col-md-3">
        <div class="prize-info-card"><div class="pi-label"><?=$icon?> <?=$lbl?></div><div class="pi-amount"><?=$val?></div></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</div>

<div class="container py-4">
  <!-- Quick search for this bond type -->
  <div class="pb-card p-4 mb-4">
    <h5 class="mb-3" style="font-family:var(--font);font-weight:700"><i class="bi bi-search me-2 text-pk"></i>Check Your <?=e($cur_bond->name)?> Number</h5>
    <div class="row g-2">
      <div class="col-md-5"><select id="bond-draw-sel" class="form-select"><option value="">Latest Draw</option><?php foreach($bond_draws as $d):?><option value="<?=$d->id?>">Draw #<?=e($d->draw_number)?> — <?=date('d M Y',strtotime($d->draw_date))?></option><?php endforeach;?></select></div>
      <div class="col-md-5"><input type="text" id="bond-num" class="form-control mono" placeholder="Enter your bond number…" maxlength="10"></div>
      <div class="col-md-2"><button class="btn-pk w-100 py-2" onclick="bondPageSearch(<?=$cur_bond->id?>)" style="border-radius:var(--r-sm)"><i class="bi bi-search"></i> Search</button></div>
    </div>
    <div id="bond-result" class="mt-3"></div>
  </div>

  <!-- Draws history table -->
  <h4 class="mb-3" style="font-family:var(--font);font-weight:700">Draw History (<?=number_format($total_bd)?>)</h4>
  <?php if(!$bond_draws):?>
  <div class="no-results"><span class="icon">📋</span><p>No draw results for this bond type yet.</p></div>
  <?php else:?>
  <div class="pb-card" style="overflow:hidden">
    <table class="table history-table mb-0">
      <thead><tr><th>Draw #</th><th>Date</th><th>City</th><th>1st Prize Winner</th><th>Total Winners</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach($bond_draws as $d):
          $fw=db_winners($d->id,'first');
        ?>
        <tr>
          <td class="fw-bold mono">#<?=e($d->draw_number)?></td>
          <td><?=date('d M Y',strtotime($d->draw_date))?></td>
          <td><?=e($d->city)?></td>
          <td><span class="number-chip chip-first"><?=$fw?e($fw[0]->winning_number):'—'?></span></td>
          <td><?=number_format($d->total_winners??0)?></td>
          <td><a href="<?=sp(['page'=>'draw','id'=>$d->id])?>" class="btn btn-sm btn-outline-success" style="font-size:.78rem">View Full Result</a></td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>
  <!-- Pagination -->
  <?php if($total_bd_pages>1):?>
  <nav class="mt-3"><ul class="pagination pagination-sm flex-wrap gap-1 justify-content-center">
    <?php for($i=1;$i<=$total_bd_pages;$i++):?><li class="page-item <?=$i===$bp?'active':''?>"><a class="page-link" href="<?=sp(['page'=>'bond','type'=>$bond_slug,'pg'=>$i])?>"><?=$i?></a></li><?php endfor;?>
  </ul></nav>
  <?php endif;?>
  <?php endif;?>
</div>

<?php
// ════════════════════════════════════════════════════════════
// ■  DRAW RESULT PAGE
// ════════════════════════════════════════════════════════════
elseif($page==='draw'&&$draw_detail):
    $w1=db_winners($draw_detail->id,'first');
    $w2=db_winners($draw_detail->id,'second');
    $w3=db_winners($draw_detail->id,'third');
?>
<!-- Breadcrumb -->
<div class="breadcrumb-wrap"><div class="container"><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="<?=sp(['page'=>'home'])?>">Home</a></li><li class="breadcrumb-item"><a href="<?=sp(['page'=>'bond','type'=>$draw_detail->bond_slug])?>"><?=e($draw_detail->bond_name)?></a></li><li class="breadcrumb-item active">Draw #<?=e($draw_detail->draw_number)?></li></ol></nav></div></div>

<div class="draw-result-header">
  <div class="container">
    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
      <?php if($draw_detail->is_premium):?><span class="badge-premium">PREMIUM</span><?php endif;?>
      <h1 class="text-white mb-0" style="font-size:1.7rem"><?=e($draw_detail->bond_name)?> — Draw #<?=e($draw_detail->draw_number)?> Result</h1>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <span class="draw-info-pill"><i class="bi bi-calendar3 me-1"></i><?=date('d F Y',strtotime($draw_detail->draw_date))?></span>
      <span class="draw-info-pill"><i class="bi bi-geo-alt me-1"></i><?=e($draw_detail->city)?></span>
      <span class="draw-info-pill"><i class="bi bi-trophy me-1"></i><?=number_format($draw_detail->total_winners??0)?> Winners</span>
    </div>
    <div class="mt-3 d-flex flex-wrap gap-2">
      <?php if($draw_detail->pdf_url):?><a href="<?=e($draw_detail->pdf_url)?>" target="_blank" class="btn btn-sm btn-light text-danger fw-semibold"><i class="bi bi-file-earmark-pdf me-1"></i>Official PDF</a><?php endif;?>
      <button onclick="window.print()" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)"><i class="bi bi-printer me-1"></i>Print</button>
      <button onclick="copyShareLink()" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)"><i class="bi bi-share me-1"></i>Share</button>
    </div>
  </div>
</div>

<div class="container pb-4">
  <!-- Quick search on this draw -->
  <div class="pb-card p-3 mb-4">
    <div class="row g-2 align-items-center">
      <div class="col-auto"><label class="form-label mb-0 fw-semibold text-pk"><i class="bi bi-search me-1"></i>Check your number in this draw:</label></div>
      <div class="col"><input type="text" id="draw-search-num" class="form-control mono" placeholder="Enter bond number…" maxlength="10"></div>
      <div class="col-auto"><button class="btn-pk px-4 py-2" onclick="doDrawSearch(<?=$draw_detail->id?>)" style="border-radius:var(--r-sm)">Search</button></div>
    </div>
    <div id="draw-search-result" class="mt-3"></div>
  </div>

  <?php if(!$w1&&!$w2&&!$w3):?>
  <div class="no-results"><span class="icon">📋</span><p>No winning numbers have been entered for this draw yet.</p></div>
  <?php else:?>

  <!-- 1st Prize -->
  <?php if($w1):?>
  <div class="prize-section prize-first">
    <div class="prize-header">
      <div class="prize-title">🥇 First Prize</div>
      <span class="prize-amount-badge" style="background:var(--gold);color:#fff"><?=fmt((int)$draw_detail->first_prize_amount)?></span>
    </div>
    <div class="numbers-grid">
      <?php foreach($w1 as $w):?><span class="number-chip chip-first" style="font-size:1.1rem;padding:.5rem 1rem"><?=e($w->winning_number)?></span><?php endforeach;?>
    </div>
  </div>
  <?php endif;?>

  <!-- 2nd Prize -->
  <?php if($w2):?>
  <div class="prize-section prize-second">
    <div class="prize-header">
      <div class="prize-title">🥈 Second Prize</div>
      <span class="prize-amount-badge" style="background:var(--silver);color:#fff"><?=fmt((int)$draw_detail->second_prize_amount)?> × <?=count($w2)?></span>
    </div>
    <div class="numbers-grid">
      <?php foreach($w2 as $w):?><span class="number-chip chip-second"><?=e($w->winning_number)?></span><?php endforeach;?>
    </div>
  </div>
  <?php endif;?>

  <!-- 3rd Prize -->
  <?php if($w3):?>
  <div class="prize-section prize-third">
    <div class="prize-header">
      <div class="prize-title">🥉 Third Prize</div>
      <span class="prize-amount-badge" style="background:var(--pk);color:#fff"><?=fmt((int)$draw_detail->third_prize_amount)?> × <?=count($w3)?></span>
    </div>
    <div class="numbers-grid">
      <?php foreach($w3 as $w):?><span class="number-chip chip-third"><?=e($w->winning_number)?></span><?php endforeach;?>
    </div>
  </div>
  <?php endif;?>
  <?php endif;?>

  <!-- Related Bond Type Link -->
  <div class="mt-4 text-center">
    <a href="<?=sp(['page'=>'bond','type'=>$draw_detail->bond_slug])?>" class="btn btn-outline-success"><i class="bi bi-arrow-left me-1"></i>All <?=e($draw_detail->bond_name)?> Draws</a>
  </div>
</div>

<?php
// ════════════════════════════════════════════════════════════
// ■  SEARCH PAGE
// ════════════════════════════════════════════════════════════
elseif($page==='search'):
?>
<div class="breadcrumb-wrap"><div class="container"><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="<?=sp(['page'=>'home'])?>">Home</a></li><li class="breadcrumb-item active">Check Bond Number</li></ol></nav></div></div>

<section class="pb-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="text-center mb-4">
          <div class="section-eyebrow">Instant Checker</div>
          <h1 class="section-title">Search Prize Bond Number</h1>
          <p class="section-sub mx-auto">Check single or multiple bond numbers against any draw. Upload a CSV/Excel file for batch checking.</p>
        </div>

        <div class="pb-card p-4">
          <ul class="nav search-tabs mb-4" id="searchPageTabs">
            <li class="nav-item"><button class="nav-link active" data-tab="sp-single"><i class="bi bi-123 me-1"></i>Single Number</button></li>
            <li class="nav-item"><button class="nav-link" data-tab="sp-bulk"><i class="bi bi-list-ul me-1"></i>Multiple Numbers</button></li>
            <li class="nav-item"><button class="nav-link" data-tab="sp-file"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Upload File</button></li>
          </ul>

          <!-- Single -->
          <div id="tab-sp-single">
            <div class="row g-3 mb-3">
              <div class="col-md-4"><label class="form-label">Bond Type</label><select id="sp-bt" class="form-select"><option value="">All Bond Types</option><?php foreach($bond_types as $bt):?><option value="<?=$bt->id?>"><?=e($bt->name)?></option><?php endforeach;?></select></div>
              <div class="col-md-4"><label class="form-label">Draw (optional)</label><select id="sp-did" class="form-select"><option value="">Latest Draw</option></select></div>
              <div class="col-md-4"><label class="form-label">Bond Number *</label><input type="text" id="sp-num" class="form-control mono" placeholder="e.g. 123456" maxlength="10"></div>
            </div>
            <button class="btn-pk px-4 py-2 mb-3" onclick="doSearchPage()" style="border-radius:var(--r-sm);min-width:140px"><i class="bi bi-search me-1"></i>Check Number</button>
            <div id="sp-result"></div>
          </div>

          <!-- Bulk -->
          <div id="tab-sp-bulk" style="display:none">
            <div class="row g-3 mb-3">
              <div class="col-md-4"><label class="form-label">Bond Type</label><select id="spb-bt" class="form-select"><option value="">All Bond Types</option><?php foreach($bond_types as $bt):?><option value="<?=$bt->id?>"><?=e($bt->name)?></option><?php endforeach;?></select></div>
              <div class="col-md-4"><label class="form-label">Draw (optional)</label><select id="spb-did" class="form-select"><option value="">Latest Draw</option></select></div>
            </div>
            <label class="form-label">Paste Bond Numbers (up to 500, one per line or comma-separated)</label>
            <textarea id="spb-nums" class="form-control mono mb-3" rows="8" placeholder="123456&#10;789012&#10;345678&#10;..."></textarea>
            <button class="btn-pk px-4 py-2" onclick="doBulkPage()" style="border-radius:var(--r-sm)"><i class="bi bi-search me-1"></i>Check All Numbers</button>
            <div id="spb-result" class="mt-3"></div>
          </div>

          <!-- File Upload -->
          <div id="tab-sp-file" style="display:none">
            <div class="row g-3 mb-4">
              <div class="col-md-4"><label class="form-label">Bond Type</label><select id="spf-bt" class="form-select"><option value="">All Bond Types</option><?php foreach($bond_types as $bt):?><option value="<?=$bt->id?>"><?=e($bt->name)?></option><?php endforeach;?></select></div>
              <div class="col-md-4"><label class="form-label">Draw (optional)</label><select id="spf-did" class="form-select"><option value="">Latest Draw</option></select></div>
            </div>
            <div class="p-4 rounded" style="border:2px dashed var(--border);background:var(--g100);text-align:center">
              <i class="bi bi-cloud-upload" style="font-size:2.5rem;color:var(--pk);display:block;margin-bottom:.5rem"></i>
              <p class="mb-2" style="color:var(--muted)">Upload a CSV or Excel file with bond numbers</p>
              <input type="file" id="spf-file" class="form-control" accept=".csv,.xlsx,.xls,.txt" style="max-width:360px;margin:0 auto">
              <small class="text-muted d-block mt-2">CSV: one number per row, or two columns (prize_type, number). Max 500 numbers.</small>
            </div>
            <button class="btn-pk px-4 py-2 mt-3" onclick="doFileSearch()" style="border-radius:var(--r-sm)"><i class="bi bi-search me-1"></i>Check File Numbers</button>
            <div id="spf-result" class="mt-3"></div>
          </div>
        </div>

        <!-- Search History -->
        <div class="mt-4" id="search-history-wrap" style="display:none">
          <h5 style="font-family:var(--font);font-weight:700;font-size:.95rem">Recent Searches <button onclick="clearHistory()" class="btn btn-sm btn-link text-muted py-0" style="font-size:.8rem">Clear</button></h5>
          <div id="search-history-list" class="d-flex flex-wrap gap-2"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
// ════════════════════════════════════════════════════════════
// ■  SCHEDULE PAGE
// ════════════════════════════════════════════════════════════
elseif($page==='schedule'):
    $all_upcoming=db_schedules('upcoming',50);
?>
<div class="breadcrumb-wrap"><div class="container"><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="<?=sp(['page'=>'home'])?>">Home</a></li><li class="breadcrumb-item active">Draw Schedule</li></ol></nav></div></div>
<section class="pb-section">
  <div class="container">
    <div class="section-eyebrow">2025 Calendar</div>
    <h1 class="section-title">Upcoming Prize Bond Draw Schedule</h1>
    <p class="section-sub">All scheduled prize bond draws for 2025. Set a reminder so you never miss a result.</p>

    <?php if(!$all_upcoming):?>
    <div class="no-results"><span class="icon">📅</span><p>No upcoming draws scheduled. Check back soon.</p></div>
    <?php else:?>
    <div class="schedule-table">
      <table class="table mb-0 w-100">
        <thead><tr><th>Bond Type</th><th>Draw #</th><th>Draw Date</th><th>City</th><th>Venue</th><th>Countdown</th></tr></thead>
        <tbody>
          <?php foreach($all_upcoming as $s):?>
          <tr>
            <td>
              <a href="<?=sp(['page'=>'bond','type'=>$s->bond_slug])?>" class="text-decoration-none">
                <span class="badge me-1" style="background:var(--pk)">Rs.<?=number_format($s->denomination)?></span>
              </a>
              <?=$s->is_premium?'<span class="badge-premium">Premium</span>':''?>
            </td>
            <td class="mono fw-bold">#<?=e($s->draw_number)?></td>
            <td><strong><?=date('d M Y',strtotime($s->draw_date))?></strong><br><small class="text-muted"><?=date('l',strtotime($s->draw_date))?></small></td>
            <td><i class="bi bi-geo-alt me-1 text-pk"></i><?=e($s->city)?></td>
            <td class="text-muted" style="font-size:.82rem"><?=$s->venue?e($s->venue):'National Savings Centre'?></td>
            <td>
              <div class="countdown-box" data-date="<?=e($s->draw_date)?>">
                <span class="cd-unit"><span class="cd-days">–</span><small class="cd-label">days</small></span>
                <span class="cd-unit"><span class="cd-hours">–</span><small class="cd-label">hrs</small></span>
                <span class="cd-unit"><span class="cd-mins">–</span><small class="cd-label">min</small></span>
                <span class="cd-unit"><span class="cd-secs">–</span><small class="cd-label">sec</small></span>
              </div>
            </td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>
    <?php endif;?>

    <!-- Bond type schedule overview -->
    <div class="row g-3 mt-4">
      <?php foreach($bond_types as $bt):?>
      <div class="col-md-4 col-lg-3">
        <a href="<?=sp(['page'=>'bond','type'=>$bt->slug])?>" class="pb-card p-3 d-block" style="color:var(--text)">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold" style="font-family:var(--font);font-size:.95rem">Rs.<?=number_format($bt->denomination)?></div>
              <div style="font-size:.78rem;color:var(--muted)"><?=$bt->draws_per_year?> draws / year<?=$bt->is_premium?' ⭐':''?></div>
            </div>
            <i class="bi bi-chevron-right text-pk"></i>
          </div>
        </a>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<?php
// ════════════════════════════════════════════════════════════
// ■  ABOUT PAGE
// ════════════════════════════════════════════════════════════
elseif($page==='about'):
?>
<div class="breadcrumb-wrap"><div class="container"><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="<?=sp(['page'=>'home'])?>">Home</a></li><li class="breadcrumb-item active">About Us</li></ol></nav></div></div>
<section class="pb-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="section-eyebrow">Who We Are</div>
        <h1 class="section-title">About <?=SITE_NAME?></h1>
        <div class="pb-card p-4 p-lg-5 mt-4">
          <p style="font-size:1.05rem;line-height:1.8;color:var(--text)" class="mb-4"><?=SITE_NAME?> is Pakistan's most trusted and up-to-date Prize Bond result website. We provide instant, accurate, and complete draw results for all nine denominations issued by the State Bank of Pakistan — from Rs.100 to Rs.40,000 Premium Prize Bonds.</p>
          <div class="row g-4 mb-4">
            <?php foreach([['🎯','Our Mission','To be the most reliable, fastest, and easiest way for Pakistanis to check their prize bond results online.'],['📊','Data Source','All results are sourced directly from official State Bank of Pakistan (SBP) draw announcements and government records.'],['⚡','Speed','Results are available immediately after official announcement — no waiting, no delays.'],['🔒','Privacy','We do not store any personal data. Bond number searches are anonymised and used only for analytics.']] as [$icon,$title,$text]):?>
            <div class="col-md-6">
              <div class="d-flex gap-3">
                <div style="font-size:1.8rem;flex-shrink:0"><?=$icon?></div>
                <div><h5 style="font-family:var(--font);font-weight:700;font-size:1rem" class="mb-1"><?=$title?></h5><p style="font-size:.875rem;color:var(--muted);margin:0"><?=$text?></p></div>
              </div>
            </div>
            <?php endforeach;?>
          </div>
          <div style="background:var(--pk-l);border-radius:var(--r);padding:1.5rem;border-left:4px solid var(--pk)">
            <h5 style="font-family:var(--font);font-weight:700;color:var(--pk)" class="mb-2">Disclaimer</h5>
            <p style="font-size:.875rem;color:var(--muted);margin:0"><?=SITE_NAME?> is an independent website and is not affiliated with or endorsed by the State Bank of Pakistan, National Savings, or any government body. While we strive for accuracy, always verify results at <a href="https://www.sbp.org.pk" target="_blank" style="color:var(--pk)">sbp.org.pk</a> for official records.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
// ════════════════════════════════════════════════════════════
// ■  CONTACT PAGE
// ════════════════════════════════════════════════════════════
elseif($page==='contact'):
$contact_sent=false;$contact_err='';
if(isset($_POST['send_contact'])){
    $cn=strip_tags($_POST['contact_name']??'');
    $ce=filter_var($_POST['contact_email']??'',FILTER_SANITIZE_EMAIL);
    $cs=strip_tags($_POST['contact_subject']??'');
    $cm=strip_tags($_POST['contact_message']??'');
    if(!$cn||!filter_var($ce,FILTER_VALIDATE_EMAIL)||!$cm){$contact_err='Please fill in all required fields.';}
    else{
        // In production: use mail() or SMTP here
        // mail('admin@yourdomain.com', $cs, $cm, "From: $ce\r\nReply-To: $ce");
        $contact_sent=true;
    }
}
?>
<div class="breadcrumb-wrap"><div class="container"><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="<?=sp(['page'=>'home'])?>">Home</a></li><li class="breadcrumb-item active">Contact Us</li></ol></nav></div></div>
<section class="pb-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="section-eyebrow">Get in Touch</div>
        <h1 class="section-title">Contact Us</h1>
        <div class="row g-4 mt-2">
          <div class="col-md-7">
            <div class="pb-card p-4">
              <?php if($contact_sent):?>
              <div style="text-align:center;padding:2rem">
                <div style="font-size:3rem">✅</div>
                <h4 style="font-family:var(--font);font-weight:700;margin:.75rem 0 .5rem">Message Sent!</h4>
                <p style="color:var(--muted)">Thank you for contacting us. We'll get back to you within 24 hours.</p>
                <a href="<?=sp(['page'=>'home'])?>" class="btn-pk px-4 py-2 mt-2" style="border-radius:var(--r-sm);display:inline-block">Go to Homepage</a>
              </div>
              <?php else:?>
              <h5 style="font-family:var(--font);font-weight:700" class="mb-3">Send a Message</h5>
              <?php if($contact_err):?><div class="alert alert-danger py-2 mb-3"><?=e($contact_err)?></div><?php endif;?>
              <form method="POST">
                <div class="row g-3">
                  <div class="col-md-6"><label class="form-label">Your Name *</label><input type="text" name="contact_name" class="form-control" placeholder="Muhammad Ali" required></div>
                  <div class="col-md-6"><label class="form-label">Email Address *</label><input type="email" name="contact_email" class="form-control" placeholder="you@example.com" required></div>
                  <div class="col-12"><label class="form-label">Subject</label><input type="text" name="contact_subject" class="form-control" placeholder="e.g. Result correction, Suggestion…"></div>
                  <div class="col-12"><label class="form-label">Message *</label><textarea name="contact_message" class="form-control" rows="5" placeholder="Your message here…" required></textarea></div>
                  <div class="col-12"><button type="submit" name="send_contact" class="btn-pk px-5 py-2" style="border-radius:var(--r-sm)"><i class="bi bi-send me-1"></i>Send Message</button></div>
                </div>
              </form>
              <?php endif;?>
            </div>
          </div>
          <div class="col-md-5">
            <div class="pb-card p-4 mb-3">
              <h6 style="font-family:var(--font);font-weight:700;margin-bottom:1rem">Contact Information</h6>
              <?php foreach([['bi-envelope-fill','Email','info@prizebond.pk'],['bi-clock-fill','Response Time','Within 24 hours'],['bi-geo-alt-fill','Based In','Pakistan'],['bi-globe','Official Data','sbp.org.pk']] as [$icon,$label,$val]):?>
              <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:36px;height:36px;background:var(--pk-l);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;color:var(--pk);flex-shrink:0"><i class="<?=$icon?>"></i></div>
                <div><div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px"><?=$label?></div><div style="font-weight:600;font-size:.9rem"><?=$val?></div></div>
              </div>
              <?php endforeach;?>
            </div>
            <div class="pb-card p-4" style="background:var(--pk-l);border:1px solid rgba(11,143,58,.2)">
              <h6 style="font-family:var(--font);font-weight:700;color:var(--pk)" class="mb-2"><i class="bi bi-lightbulb-fill me-1"></i>Quick Help</h6>
              <p style="font-size:.85rem;color:var(--muted);margin-bottom:.75rem">Before contacting, check our FAQ on the homepage — most questions are answered there.</p>
              <a href="<?=sp(['page'=>'home'])?>#faq" style="font-size:.85rem;color:var(--pk);font-weight:600">View FAQ →</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
// ════════════════════════════════════════════════════════════
// ■  PRIVACY POLICY
// ════════════════════════════════════════════════════════════
elseif($page==='privacy'):
?>
<div class="breadcrumb-wrap"><div class="container"><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="<?=sp(['page'=>'home'])?>">Home</a></li><li class="breadcrumb-item active">Privacy Policy</li></ol></nav></div></div>
<section class="pb-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <h1 class="section-title">Privacy Policy</h1>
        <p class="text-muted mb-4">Last updated: <?=date('d F Y')?></p>
        <div class="pb-card p-4 p-lg-5">
          <?php foreach([
            ['Information We Collect','We collect minimal information necessary to operate the service: (1) <strong>Search logs</strong> — bond numbers searched, stored as anonymised hashed records without any personal identifiers. (2) <strong>Newsletter subscriptions</strong> — email address and optional name, only when you voluntarily subscribe. (3) <strong>Server logs</strong> — standard web server access logs (IP address, browser, page visited) retained for 30 days for security purposes.'],
            ['How We Use Information','Search logs are used only to generate aggregate statistics (e.g., "1,200 searches today"). We never associate search queries with individual users. Email addresses collected for newsletter subscriptions are used exclusively to send prize bond result notifications. We do not sell, share, or transfer your data to third parties.'],
            ['Cookies','This website uses a single session cookie for admin authentication purposes only. We do not use tracking cookies, advertising cookies, or third-party analytics cookies. Dark mode preference is stored in your browser\'s localStorage (never sent to our server).'],
            ['Google AdSense &amp; Analytics','If AdSense or Google Analytics is enabled, Google may set cookies on your device per their own privacy policies. You can opt out via <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" style="color:var(--pk)">Google\'s opt-out tool</a>.'],
            ['Data Security','We use standard security practices including prepared SQL statements (SQL injection protection), CSRF token validation for admin forms, and HTTPS (configure on your server). No payment data is collected or processed on this site.'],
            ['Your Rights','You may request deletion of your email from our newsletter list at any time by contacting us. Search logs are anonymised and cannot be linked back to individuals.'],
            ['Contact','For privacy questions, contact us via the <a href="'.sp(['page'=>'contact']).'" style="color:var(--pk)">Contact page</a>.'],
          ] as [$heading,$body]):?>
          <h5 style="font-family:var(--font);font-weight:700;color:var(--pk);margin-bottom:.6rem"><?=$heading?></h5>
          <p style="color:var(--muted);line-height:1.8;margin-bottom:1.5rem"><?=$body?></p>
          <?php endforeach;?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
// ════════════════════════════════════════════════════════════
// ■  TERMS & CONDITIONS
// ════════════════════════════════════════════════════════════
elseif($page==='terms'):
?>
<div class="breadcrumb-wrap"><div class="container"><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="<?=sp(['page'=>'home'])?>">Home</a></li><li class="breadcrumb-item active">Terms &amp; Conditions</li></ol></nav></div></div>
<section class="pb-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <h1 class="section-title">Terms &amp; Conditions</h1>
        <p class="text-muted mb-4">By using <?=SITE_NAME?>, you agree to the following terms.</p>
        <div class="pb-card p-4 p-lg-5">
          <?php foreach([
            ['1. Accuracy of Results','While we make every effort to ensure accuracy, prize bond results are sourced from official SBP announcements. Always verify your result at the official State Bank of Pakistan website before making any financial decisions or claiming a prize.'],
            ['2. No Official Affiliation','This website is an independent service and is not affiliated with, endorsed by, or connected to the State Bank of Pakistan, Government of Pakistan, or National Savings.'],
            ['3. Acceptable Use','You may use this website only for lawful purposes. You must not attempt to hack, overload, or manipulate the website, its database, or the search functionality.'],
            ['4. Intellectual Property','The design, code, and presentation of this website are proprietary. Results data belongs to the State Bank of Pakistan. You may share individual result pages but may not scrape or bulk-export data.'],
            ['5. Limitation of Liability','This website is provided "as is" with no warranties. We are not liable for any financial decisions made based on results shown here. Always confirm winnings at official SBP offices.'],
            ['6. Advertising','This website may display third-party advertisements. We are not responsible for the content of those advertisements.'],
            ['7. Changes','We reserve the right to update these terms at any time. Continued use of the site constitutes acceptance of updated terms.'],
          ] as [$heading,$body]):?>
          <h5 style="font-family:var(--font);font-weight:700;margin-bottom:.6rem"><?=$heading?></h5>
          <p style="color:var(--muted);line-height:1.8;margin-bottom:1.5rem"><?=$body?></p>
          <?php endforeach;?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
// ════════════════════════════════════════════════════════════
// ■  404 / UNKNOWN PAGE
// ════════════════════════════════════════════════════════════
else:
?>
<div class="container py-5 text-center">
  <div style="font-size:5rem;margin-bottom:1rem">🔍</div>
  <h1 style="font-size:5rem;font-weight:800;color:var(--pk)">404</h1>
  <h3 class="mb-3">Page Not Found</h3>
  <p class="text-muted mb-4">The page you're looking for doesn't exist.</p>
  <a href="<?=sp(['page'=>'home'])?>" class="btn-pk px-5 py-3" style="border-radius:var(--r-sm);font-size:1rem;display:inline-block">← Go to Homepage</a>
</div>
<?php endif;?>

<!-- ════ FOOTER ════ -->
<footer class="pb-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="footer-brand"><i class="bi bi-award-fill me-1"></i>Prize Bond<span>PK</span></div>
        <p class="footer-about">Pakistan's most trusted source for prize bond draw results. We provide instant, accurate, and complete results for all 9 denominations directly from official SBP data.</p>
        <div class="social-links">
          <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" title="Twitter/X"><i class="bi bi-twitter-x"></i></a>
          <a href="#" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
          <a href="#" title="YouTube"><i class="bi bi-youtube"></i></a>
        </div>
      </div>
      <div class="col-lg-2 col-md-4">
        <div class="footer-title">Bond Types</div>
        <ul class="footer-links">
          <?php foreach($bond_types as $bt):?>
          <li><a href="<?=sp(['page'=>'bond','type'=>$bt->slug])?>">Rs.<?=number_format($bt->denomination)?></a></li>
          <?php endforeach;?>
        </ul>
      </div>
      <div class="col-lg-2 col-md-4">
        <div class="footer-title">Quick Links</div>
        <ul class="footer-links">
          <li><a href="<?=sp(['page'=>'home'])?>">Home</a></li>
          <li><a href="<?=sp(['page'=>'search'])?>">Check Bond Number</a></li>
          <li><a href="<?=sp(['page'=>'schedule'])?>">Draw Schedule</a></li>
          <li><a href="<?=sp(['page'=>'about'])?>">About Us</a></li>
          <li><a href="<?=sp(['page'=>'contact'])?>">Contact Us</a></li>
          <li><a href="<?=sp(['page'=>'privacy'])?>">Privacy Policy</a></li>
          <li><a href="<?=sp(['page'=>'terms'])?>">Terms &amp; Conditions</a></li>
        </ul>
      </div>
      <div class="col-lg-4 col-md-4">
        <div class="footer-title">About Prize Bonds</div>
        <p style="font-size:.875rem;line-height:1.7;color:rgba(255,255,255,.6)">Prize Bonds are issued by the Government of Pakistan through the State Bank of Pakistan. All draw data is sourced from official SBP announcements.</p>
        <a href="https://www.sbp.org.pk" target="_blank" rel="noopener" style="font-size:.82rem;color:var(--pk-m)"><i class="bi bi-box-arrow-up-right me-1"></i>State Bank of Pakistan →</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">
      © <?=date('Y')?> <?=SITE_NAME?>. Prize bond data sourced from official SBP records.
      &nbsp;|&nbsp; <a href="<?=sp(['page'=>'admin'])?>" style="color:rgba(255,255,255,.4)">Admin</a>
    </div>
  </div>
</footer>

<!-- Back to top -->
<button id="back-to-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Back to top"><i class="bi bi-arrow-up"></i></button>

<?php endif; // end public site ?>

<!-- ════ SCRIPTS ════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
// ============================================================
// PRIZE BOND PK — Main JavaScript
// ============================================================

const SELF = location.pathname + location.search.split('&').filter(p=>!p.startsWith('ajax')).join('&');
const AJAX_URL = location.pathname;

// ── Dark Mode ─────────────────────────────────────────────
function applyTheme(t){
    document.documentElement.setAttribute('data-theme',t);
    const i1=document.getElementById('dark-icon'),i2=document.getElementById('dark-icon-m');
    const cls=t==='dark'?'bi-sun-fill':'bi-moon-fill';
    if(i1){i1.className='bi '+cls;}
    if(i2){i2.className='bi '+cls;}
}
function toggleDark(){
    const cur=document.documentElement.getAttribute('data-theme')||'light';
    const nxt=cur==='dark'?'light':'dark';
    localStorage.setItem('pb-theme',nxt);
    applyTheme(nxt);
}
applyTheme(localStorage.getItem('pb-theme')||'light');

// ── Back to Top ────────────────────────────────────────────
const btn=document.getElementById('back-to-top');
if(btn){window.addEventListener('scroll',()=>{btn.classList.toggle('show',window.scrollY>400)},{ passive:true });}

// ── Countdown Timers ──────────────────────────────────────
function updateCountdowns(){
    document.querySelectorAll('.countdown-box').forEach(box=>{
        const dateStr=box.getAttribute('data-date');
        if(!dateStr)return;
        const target=new Date(dateStr+'T10:30:00').getTime();
        const now=Date.now();
        const diff=target-now;
        if(diff<=0){box.innerHTML='<span style="color:var(--pk);font-weight:700;font-size:.82rem">Draw Day!</span>';return;}
        const days=Math.floor(diff/86400000);
        const hrs=Math.floor((diff%86400000)/3600000);
        const mins=Math.floor((diff%3600000)/60000);
        const secs=Math.floor((diff%60000)/1000);
        const d=box.querySelector('.cd-days'),h=box.querySelector('.cd-hours'),m=box.querySelector('.cd-mins'),s=box.querySelector('.cd-secs');
        if(d)d.textContent=days;
        if(h)h.textContent=String(hrs).padStart(2,'0');
        if(m)m.textContent=String(mins).padStart(2,'0');
        if(s)s.textContent=String(secs).padStart(2,'0');
    });
}
updateCountdowns();
setInterval(updateCountdowns,1000);

// ── Animated Counters ─────────────────────────────────────
const counters=document.querySelectorAll('.counter');
if(counters.length){
    const io=new IntersectionObserver(entries=>{
        entries.forEach(e=>{
            if(!e.isIntersecting)return;
            const el=e.target;
            const target=parseInt(el.dataset.target)||0;
            if(!target){return;}
            let start=0,duration=1600,step=target/duration*16;
            const run=()=>{
                start+=step;
                if(start>=target){el.textContent=target.toLocaleString();return;}
                el.textContent=Math.floor(start).toLocaleString();
                requestAnimationFrame(run);
            };
            requestAnimationFrame(run);
            io.unobserve(el);
        });
    },{threshold:.3});
    counters.forEach(c=>io.observe(c));
}

// ── Search Tabs ────────────────────────────────────────────
document.querySelectorAll('.search-tabs .nav-link').forEach(btn=>{
    btn.addEventListener('click',function(){
        const parent=this.closest('.card,.pb-card,.col-lg-9,section,div');
        const tabs=this.closest('.search-tabs').querySelectorAll('.nav-link');
        tabs.forEach(t=>t.classList.remove('active'));
        this.classList.add('active');
        const tab=this.getAttribute('data-tab');
        // Find all sibling tab panels (id starts with "tab-")
        const allPanels=document.querySelectorAll('[id^="tab-"]');
        allPanels.forEach(p=>{p.style.display='none';});
        const panel=document.getElementById('tab-'+tab);
        if(panel)panel.style.display='';
    });
});

// ── AJAX Helper ────────────────────────────────────────────
async function pbPost(action,data){
    const fd=new FormData();
    Object.entries(data).forEach(([k,v])=>fd.append(k,v));
    try{
        const r=await fetch(AJAX_URL+'?ajax=1&action='+action,{method:'POST',body:fd});
        return await r.json();
    }catch(e){return{ok:false,msg:'Network error. Please try again.'};}
}

// ── Result Card HTML ──────────────────────────────────────
function resultCardHTML(res){
    if(!res.ok)return`<div class="search-result-card result-lost"><i class="bi bi-exclamation-circle text-danger me-2"></i>${res.msg||'Search failed'}</div>`;
    if(!res.found)return`<div class="search-result-card result-lost"><div class="d-flex align-items-center gap-2 mb-2"><span style="font-size:1.8rem">😔</span><div><strong style="font-family:var(--font);font-size:1rem">Not a Winner</strong><div style="color:var(--muted);font-size:.85rem">Bond number <strong class="mono">${res.number}</strong> was not found in the selected draw.</div></div></div></div>`;
    let html=``;
    (res.results||[]).forEach(r=>{
        const colors={First:'#F59E0B',Second:'#64748B',Third:'var(--pk)'};
        html+=`<div class="search-result-card result-won"><div class="d-flex align-items-start gap-3 flex-wrap"><span style="font-size:2.5rem">🎉</span><div class="flex-grow-1"><div style="font-family:var(--font);font-weight:800;font-size:1.1rem;color:var(--pk)" class="mb-1">Congratulations! You Won!</div><div class="mono fw-bold" style="font-size:1.3rem;color:${colors[r.prize_type]||'var(--pk)'}">${r.number}</div><div class="d-flex flex-wrap gap-2 mt-2"><span class="badge" style="background:${colors[r.prize_type]};font-size:.85rem">${r.prize_type} Prize</span><span class="badge bg-light text-dark border">${r.prize_amount}</span></div><div style="font-size:.82rem;color:var(--muted);margin-top:.5rem"><i class="bi bi-calendar3 me-1"></i>${r.draw_date} &nbsp;|&nbsp; <i class="bi bi-geo-alt me-1"></i>${r.city} &nbsp;|&nbsp; Draw #${r.draw_number}</div></div></div></div>`;
    });
    return html;
}

function bulkResultHTML(res){
    if(!res.ok)return`<div class="alert alert-danger">${res.msg}</div>`;
    let html=`<div class="row g-3 mb-2"><div class="col"><div class="pb-card p-3 text-center"><div style="font-size:2rem;font-weight:800;color:var(--pk)">${res.winners.length}</div><div style="color:var(--muted);font-size:.85rem">Winners Found</div></div></div><div class="col"><div class="pb-card p-3 text-center"><div style="font-size:2rem;font-weight:800;color:var(--muted)">${res.losers.length}</div><div style="color:var(--muted);font-size:.85rem">Not Winners</div></div></div></div>`;
    if(res.winners.length){
        html+=`<div class="pb-card p-3 mb-3"><h6 style="font-family:var(--font);font-weight:700;color:var(--pk)">🏆 Winning Numbers</h6>`;
        res.winners.forEach(w=>{html+=`<div class="d-flex align-items-center gap-2 py-2 border-bottom"><span class="number-chip chip-winner">${w.number}</span><span class="badge bg-warning text-dark">${w.prize_type}</span><span class="fw-semibold">${w.prize_amount}</span></div>`;});
        html+=`</div>`;
    }
    if(res.losers.length&&res.losers.length<=100){
        html+=`<div class="pb-card p-3"><h6 style="font-family:var(--font);font-weight:600;color:var(--muted);font-size:.85rem">Non-Winning Numbers (${res.losers.length})</h6><div>${res.losers.map(n=>`<span class="number-chip chip-third" style="opacity:.5">${n}</span>`).join('')}</div></div>`;
    }
    return html;
}

// ── Hero Search ────────────────────────────────────────────
async function heroSearch(){
    const num=document.getElementById('hero-number')?.value.trim();
    const bt=document.getElementById('hero-bond-type')?.value;
    const rd=document.getElementById('hero-result');
    if(!num||!rd)return;
    rd.style.display='block';
    rd.innerHTML='<div class="loading-wrap"><div class="spinner-pk"></div></div>';
    const res=await pbPost('search',{number:num,bond_type_id:bt||''});
    rd.innerHTML=resultCardHTML(res);
    saveHistory(num);
}
document.getElementById('hero-number')?.addEventListener('keydown',e=>{if(e.key==='Enter')heroSearch();});

// ── Search Page ────────────────────────────────────────────
async function doSearchPage(){
    const num=document.getElementById('sp-num')?.value.trim();
    const bt=document.getElementById('sp-bt')?.value;
    const did=document.getElementById('sp-did')?.value;
    const rd=document.getElementById('sp-result');
    if(!num||!rd)return;
    rd.innerHTML='<div class="loading-wrap"><div class="spinner-pk"></div></div>';
    const res=await pbPost('search',{number:num,bond_type_id:bt||'',draw_id:did||''});
    rd.innerHTML=resultCardHTML(res);
    saveHistory(num);
}
document.getElementById('sp-num')?.addEventListener('keydown',e=>{if(e.key==='Enter')doSearchPage();});

async function doBulkPage(){
    const nums=document.getElementById('spb-nums')?.value;
    const bt=document.getElementById('spb-bt')?.value;
    const did=document.getElementById('spb-did')?.value;
    const rd=document.getElementById('spb-result');
    if(!nums||!rd)return;
    rd.innerHTML='<div class="loading-wrap"><div class="spinner-pk"></div></div>';
    const res=await pbPost('bulk',{numbers:nums,bond_type_id:bt||'',draw_id:did||''});
    rd.innerHTML=bulkResultHTML(res);
}

async function doFileSearch(){
    const file=document.getElementById('spf-file')?.files[0];
    const bt=document.getElementById('spf-bt')?.value;
    const did=document.getElementById('spf-did')?.value;
    const rd=document.getElementById('spf-result');
    if(!file||!rd)return;
    rd.innerHTML='<div class="loading-wrap"><div class="spinner-pk"></div><span class="ms-2 text-muted">Reading file…</span></div>';
    const nums=await parseFile(file);
    if(!nums.length){rd.innerHTML='<div class="alert alert-warning">No valid numbers found in file.</div>';return;}
    rd.innerHTML='<div class="loading-wrap"><div class="spinner-pk"></div><span class="ms-2 text-muted">Checking '+nums.length+' numbers…</span></div>';
    const res=await pbPost('bulk',{numbers:nums.join('\n'),bond_type_id:bt||'',draw_id:did||''});
    rd.innerHTML=bulkResultHTML(res);
}

// ── Home page section search ───────────────────────────────
async function doSearch(){
    const num=document.getElementById('s-number')?.value.trim();
    const bt=document.getElementById('s-bond-type')?.value;
    const did=document.getElementById('s-draw-id')?.value;
    const rd=document.getElementById('s-result');
    if(!num||!rd)return;
    rd.innerHTML='<div class="loading-wrap"><div class="spinner-pk"></div></div>';
    const res=await pbPost('search',{number:num,bond_type_id:bt||'',draw_id:did||''});
    rd.innerHTML=resultCardHTML(res);
    saveHistory(num);
}

async function doBulk(){
    const nums=document.getElementById('b-numbers')?.value;
    const bt=document.getElementById('b-bond-type')?.value;
    const did=document.getElementById('b-draw-id')?.value;
    const rd=document.getElementById('b-result');
    if(!nums||!rd)return;
    rd.innerHTML='<div class="loading-wrap"><div class="spinner-pk"></div></div>';
    const res=await pbPost('bulk',{numbers:nums,bond_type_id:bt||'',draw_id:did||''});
    rd.innerHTML=bulkResultHTML(res);
}

async function loadBulkFile(){
    const file=document.getElementById('bulk-file')?.files[0];
    const ta=document.getElementById('b-numbers');
    if(!file||!ta)return;
    const nums=await parseFile(file);
    ta.value=nums.join('\n');
}

// ── Bond Page Search ──────────────────────────────────────
async function bondPageSearch(btid){
    const num=document.getElementById('bond-num')?.value.trim();
    const did=document.getElementById('bond-draw-sel')?.value;
    const rd=document.getElementById('bond-result');
    if(!num||!rd)return;
    rd.innerHTML='<div class="loading-wrap"><div class="spinner-pk"></div></div>';
    const res=await pbPost('search',{number:num,bond_type_id:btid,draw_id:did||''});
    rd.innerHTML=resultCardHTML(res);
}
document.getElementById('bond-num')?.addEventListener('keydown',function(e){
    if(e.key==='Enter'){const bt=this.closest('div')?.querySelector('[id^="bond-"]');bondPageSearch(bt?.dataset?.btid||0);}
});

// ── Draw Page Search ──────────────────────────────────────
async function doDrawSearch(drawId){
    const num=document.getElementById('draw-search-num')?.value.trim();
    const rd=document.getElementById('draw-search-result');
    if(!num||!rd)return;
    rd.innerHTML='<div class="loading-wrap"><div class="spinner-pk"></div></div>';
    const res=await pbPost('search',{number:num,bond_type_id:'',draw_id:drawId});
    rd.innerHTML=resultCardHTML(res);
}
document.getElementById('draw-search-num')?.addEventListener('keydown',function(e){
    const did=this.closest('div')?.querySelector('[onclick*="doDrawSearch"]')?.getAttribute('onclick')?.match(/\d+/)?.[0];
    if(e.key==='Enter'&&did)doDrawSearch(parseInt(did));
});

// ── Dynamic Draw Selector (load draws for bond type) ──────
async function loadDrawsForBondType(btSel,didSel){
    const bt=document.getElementById(btSel)?.value;
    const dd=document.getElementById(didSel);
    if(!dd)return;
    if(!bt){dd.innerHTML='<option value="">Latest Draw</option>';return;}
    const res=await pbPost('get_draws',{bond_type_id:bt});
    if(res.ok)dd.innerHTML=res.options;
}
['s-bond-type','sp-bt','b-bond-type','spb-bt','spf-bt'].forEach(sid=>{
    document.getElementById(sid)?.addEventListener('change',function(){
        const pairs={'s-bond-type':'s-draw-id','sp-bt':'sp-did','b-bond-type':'b-draw-id','spb-bt':'spb-did','spf-bt':'spf-did'};
        if(pairs[sid])loadDrawsForBondType(sid,pairs[sid]);
    });
});

// ── Newsletter Subscribe ──────────────────────────────────
async function doSubscribe(){
    const em=document.getElementById('nl-email')?.value.trim();
    const nm=document.getElementById('nl-name')?.value.trim();
    const msg=document.getElementById('nl-msg');
    if(!em||!msg)return;
    const res=await pbPost('subscribe',{email:em,name:nm||''});
    msg.textContent=res.msg||(res.ok?'Subscribed!':'Error');
    if(res.ok&&document.getElementById('nl-email'))document.getElementById('nl-email').value='';
}

// ── File Parser ────────────────────────────────────────────
async function parseFile(file){
    return new Promise(resolve=>{
        const ext=file.name.split('.').pop().toLowerCase();
        const reader=new FileReader();
        reader.onload=e=>{
            let nums=[];
            if(ext==='csv'||ext==='txt'){
                const lines=e.target.result.split('\n');
                lines.forEach(line=>{
                    const cols=line.split(',');
                    cols.forEach(c=>{const n=c.replace(/\D/g,'').trim();if(n)nums.push(n);});
                });
            }else{
                try{
                    const wb=XLSX.read(e.target.result,{type:'binary'});
                    const ws=wb.Sheets[wb.SheetNames[0]];
                    const data=XLSX.utils.sheet_to_json(ws,{header:1});
                    data.forEach(row=>{
                        row.forEach(cell=>{const n=String(cell||'').replace(/\D/g,'').trim();if(n)nums.push(n);});
                    });
                }catch(e){resolve([]);}
            }
            resolve([...new Set(nums.filter(n=>n.length>0))].slice(0,500));
        };
        ext==='csv'||ext==='txt'?reader.readAsText(file):reader.readAsBinaryString(file);
    });
}

// ── Admin File Import ─────────────────────────────────────
async function parseImportFile(){
    const file=document.getElementById('import-file')?.files[0];
    const msg=document.getElementById('parse-msg');
    if(!file){if(msg)msg.textContent='Please select a file first.';return;}
    const ext=file.name.split('.').pop().toLowerCase();
    const reader=new FileReader();
    reader.onload=e=>{
        let groups={first:[],second:[],third:[]};
        if(ext==='csv'||ext==='txt'){
            e.target.result.split('\n').forEach(line=>{
                const cols=line.split(',');
                const type=(cols[0]||'').trim().toLowerCase();
                const num=(cols[1]||cols[0]||'').replace(/\D/g,'').trim();
                if(!num)return;
                if(['first','second','third'].includes(type))groups[type].push(num);
                else if((cols[0]||'').replace(/\D/g,'').trim())groups.third.push((cols[0]).replace(/\D/g,'').trim());
            });
        }else{
            try{
                const wb=XLSX.read(e.target.result,{type:'binary'});
                const ws=wb.Sheets[wb.SheetNames[0]];
                const data=XLSX.utils.sheet_to_json(ws,{header:1});
                data.forEach(row=>{
                    const type=String(row[0]||'').trim().toLowerCase();
                    const num=String(row[1]||row[0]||'').replace(/\D/g,'').trim();
                    if(!num)return;
                    if(['first','second','third'].includes(type))groups[type].push(num);
                    else groups.third.push(String(row[0]||'').replace(/\D/g,'').trim());
                });
            }catch(e){if(msg)msg.textContent='Error reading Excel file.';return;}
        }
        const ta1=document.querySelector('[name="winners_first"]');
        const ta2=document.querySelector('[name="winners_second"]');
        const ta3=document.querySelector('[name="winners_third"]');
        if(ta1)ta1.value=groups.first.filter(Boolean).join('\n');
        if(ta2)ta2.value=groups.second.filter(Boolean).join('\n');
        if(ta3)ta3.value=groups.third.filter(Boolean).join('\n');
        if(msg)msg.innerHTML=`<span style="color:var(--pk)">✓ Parsed: ${groups.first.length} first, ${groups.second.length} second, ${groups.third.length} third prize numbers.</span>`;
    };
    ext==='csv'||ext==='txt'?reader.readAsText(file):reader.readAsBinaryString(file);
}

// ── Search History ────────────────────────────────────────
function saveHistory(num){
    if(!num)return;
    let h=JSON.parse(localStorage.getItem('pb-history')||'[]');
    h=h.filter(n=>n!==num);
    h.unshift(num);
    h=h.slice(0,10);
    localStorage.setItem('pb-history',JSON.stringify(h));
    renderHistory();
}
function renderHistory(){
    const wrap=document.getElementById('search-history-wrap');
    const list=document.getElementById('search-history-list');
    if(!wrap||!list)return;
    const h=JSON.parse(localStorage.getItem('pb-history')||'[]');
    if(!h.length){wrap.style.display='none';return;}
    wrap.style.display='block';
    list.innerHTML=h.map(n=>`<span class="number-chip chip-third" style="cursor:pointer" onclick="document.getElementById('sp-num').value='${n}';doSearchPage()">${n}</span>`).join('');
}
function clearHistory(){
    localStorage.removeItem('pb-history');
    const w=document.getElementById('search-history-wrap');
    if(w)w.style.display='none';
}
renderHistory();

// ── Share Link ────────────────────────────────────────────
function copyShareLink(){
    navigator.clipboard.writeText(location.href).then(()=>{
        const btn=event.target.closest('button');
        if(btn){const orig=btn.innerHTML;btn.innerHTML='<i class="bi bi-check-lg me-1"></i>Copied!';setTimeout(()=>btn.innerHTML=orig,2000);}
    });
}

// ── Subscribers Export (admin) ─────────────────────────────
function exportSubscribers(){
    const rows=document.querySelectorAll('.admin-table tbody tr');
    let csv='Email,Name,Subscribed\n';
    rows.forEach(r=>{
        const cells=r.querySelectorAll('td');
        if(cells.length>=3)csv+=`"${cells[0].textContent}","${cells[1].textContent}","${cells[2].textContent}"\n`;
    });
    const blob=new Blob([csv],{type:'text/csv'});
    const a=document.createElement('a');
    a.href=URL.createObjectURL(blob);
    a.download='pb-subscribers.csv';
    a.click();
}
</script>
</body>
</html>