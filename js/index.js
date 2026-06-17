$(document).ready(function(){
	if($('table#categoryIndex').length){
		$('table#categoryIndex').DataTable({
			order: [[1, 'desc']]
		});
		$('table#categoryIndex').on('click', 'tbody tr', function(){
			window.location.href = '/index/' + $(this).data('id');
		});
	}else if($('table#storyIndex').length){
		const categoryIndex = $('input#categoryId').val();
		const categoryName = $('input#categoryName').val();
		const dt = $('table#storyIndex').DataTable({
			processing: true,
			serverSide: true,
			columns: [
				{'visible' : false},
				null
			],
			ajax: {
				url: "/ajax/storyindex.php?category=" + categoryIndex
			},
			createdRow: function(row, data, dataIdx){
				$(row).attr('data-id', data[0]);
			}
		});
		$('table#storyIndex').on('click', 'tbody tr', function(){
			window.location.href = '/story/' + categoryName + '/' + ((dt.row(this).index() + 1) + (dt.page() * dt.page.len()));
		});
	}
});