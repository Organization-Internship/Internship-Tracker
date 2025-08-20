<?php
require_once __DIR__.'/../config.php'; require_once __DIR__.'/../utils/session.php'; require_login(); $u=current_user();
$apiKey=getenv('OPENROUTER_API_KEY');
function create_internship($t,$d,$uid,$stip,$dur,$skills,$kind='ai'){ $conn=db(); $stmt=$conn->prepare("INSERT INTO internships(title, description, posted_by_user_id, kind, stipend, duration, skills_required) VALUES (?,?,?,?,?,?,?)"); $stmt->bind_param('sssisss',$t,$d,$uid,$kind,$stip,$dur,$skills); $stmt->execute(); }
if($apiKey){
  $payload=[
    "model"=>"openai/gpt-4o-mini",
    "messages"=>[
      ["role"=>"system","content"=>"Generate a concise software internship posting WITHOUT any location field. Return a plain text with fields: Title:, Stipend:, Duration:, Skills:, Description: (4 bullets)."],
      ["role"=>"user","content"=>"Create one internship (online) for college students."]
    ]
  ];
  $ch=curl_init('https://openrouter.ai/api/v1/chat/completions');
  curl_setopt($ch,CURLOPT_HTTPHEADER,['Content-Type: application/json','Authorization: Bearer '.$apiKey]);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); curl_setopt($ch,CURLOPT_POST,true); curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload));
  $resp=curl_exec($ch);
  if($resp!==false){
    $data=json_decode($resp,true); $text=$data['choices'][0]['message']['content'] ?? '';
    $title='AI Internship'; $stip=''; $dur=''; $skills=''; $desc=$text;
    if($text){
      foreach(explode("\n",$text) as $line){
        if(stripos($line,'Title:')===0) $title=trim(substr($line,6));
        if(stripos($line,'Stipend:')===0) $stip=trim(substr($line,8));
        if(stripos($line,'Duration:')===0) $dur=trim(substr($line,9));
        if(stripos($line,'Skills:')===0) $skills=trim(substr($line,7));
      }
    }
    if(!$skills){ $skills='HTML,CSS,JavaScript,PHP,MySQL'; }
    if(!$dur){ $dur='8 weeks'; }
    create_internship($title,$desc,$u['id'],$stip,$dur,$skills,'ai'); echo json_encode(['status'=>'success','message'=>'AI internship generated (OpenRouter).']); exit;
  }
}
$titles=['Full-Stack Web Intern','Data Analyst Intern','Mobile App Intern','Cloud DevOps Intern','Cybersecurity Intern'];
$stipends=['₹4000/month','₹5000/month','₹0 (unpaid with certificate)','₹7000/month','₹10000/month'];
$durations=['6 weeks','8 weeks','10 weeks','12 weeks'];
$skills_list=['HTML,CSS,JS,PHP,MySQL','Python,SQL,Excel','Flutter,Dart,REST','Docker,CI/CD,Bash','OWASP,Burp Suite'];
$title=$titles[array_rand($titles)]; $stip=$stipends[array_rand($stipends)]; $dur=$durations[array_rand($durations)]; $skills=$skills_list[array_rand($skills_list)];
$desc="- Tasks: Build modules, auth, dashboards\n- Guidance: Weekly mentor review\n- Deliverables: Repo + report\n- Difficulty: Intermediate";
create_internship($title,$desc,$u['id'],$stip,$dur,$skills,'ai'); echo json_encode(['status'=>'success','message'=>'AI internship generated (mock).']);
?>