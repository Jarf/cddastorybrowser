$(document).ready(function(){
	if($('table#categoryIndex').length){
		$('table#categoryIndex').DataTable({
			order: [[1, 'desc']]
		});
		$('table#categoryIndex').on('click', 'tbody tr', function(){
			window.location.href = '/index/' + $(this).data('name');
		});
	}else if($('table#storyIndex').length){
		const categoryIndex = $('input#categoryId').val();
		const categoryName = $('input#categoryName').val();
		const dt = $('table#storyIndex').DataTable({
			processing: true,
			serverSide: true,
			columns: [
				{'visible' : false},
				{
					render: function(data, type, row, meta){
						if(meta.col === 1){
							data = '<a href="/story/' + categoryName + '/' + (meta.row + 1) + '">' + data + '</a>';
						}
						return data;
					}
				}
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