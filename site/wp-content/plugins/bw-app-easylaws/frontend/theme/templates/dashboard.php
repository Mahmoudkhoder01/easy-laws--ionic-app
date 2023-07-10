
<?php 
$data = wapi()->get_dashboard(); 

echo '<div class="home-banner"><h2>جرعتــــك القانــــونية<br> اليوميــــة</h2></div>';

app_f()->subjects_carousel([
	'subjects' => $data['subjects'],
	'title' => 'المـــــواضيـــــــع',
	'subtitle' => 'الأكــثـــــر قــــــراءة',
	'before' => '<div class="bg-light py-5">',
	'after' => '</div>'
]); 

app_f()->subjects_carousel([
	'subjects' => $data['used_subjects'],
	'title' => 'عمليات',
	'subtitle' => 'البــــحــــث',
	'before' => '<div class="bg-white py-5">',
	'after' => '</div>'
]); 

app_f()->subjects_carousel([
	'subjects' => $data['liked_subjects'],
	'title' => 'المواضيع',
	'subtitle' => 'المــــفــــضــــلة',
	'before' => '<div class="bg-white py-5">',
	'after' => '</div>'
]); 

app_f()->questions_carousel([
	'questions' => $data['liked_questions'],
	'title' => 'الأسئلة المفضلة',
	// 'subtitle' => 'الأكــثـــــر قــــــراءة',
	'before' => '<div class="bg-light py-5">',
	'after' => '</div>'
]); 
