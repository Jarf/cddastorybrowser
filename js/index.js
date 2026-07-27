$(document).ready(function(){
	if($('table#categoryIndex').length){
		$('table#categoryIndex').DataTable({
			order: [[2, 'desc']],
			responsive: true
		});
	}else if($('table#storyIndex').length){
		const dt = $('table#storyIndex').DataTable({
			order: [],
			responsive: true
		});
	}
});