$(document).ready(function(){
	if($('table#categoryIndex').length){
		$('table#categoryIndex').DataTable({
			order: [[1, 'desc']]
		});
	}else if($('table#storyIndex').length){
		const categoryIndex = $('input#categoryId').val();
		const categoryName = $('input#categoryName').val();
		const dt = $('table#storyIndex').DataTable({
			order: []
		});
	}
});