var Reea = Reea || {};
Reea.Entry = Reea.Entry || {};
Reea.Entry.Grid = Reea.Entry.Grid || {};
Reea.Entry.Grid.addToListUrl = '';
Reea.Entry.Grid.removeFromListUrl = '';

Reea.Entry.Grid.addToMultipleEntriesList = function(p_iProductId) {
	
	$j.ajax({
		url: Reea.Entry.Grid.addToListUrl,
		type: 'POST',
		data: {
			id: p_iProductId,
			form_key: Reea.Entry.Grid.formKey
		},
		dataType: 'json',
		success: function (p_oJSON) {
			
			var l_oContainer = $j('#add_to_multiple_entries_list_' + p_iProductId).parent();
			$j('#add_to_multiple_entries_list_' + p_iProductId).fadeOut(function() {
				$j(this).remove();
				$j(l_oContainer).append('<button style="display: none;" class="remove-from-list scalable delete" type="button" title="Remove from Multiple Entries List" id="remove_from_multiple_entries_list_' + p_iProductId + '">' + 
				'<span><span><span><a href="javascript: void(0);">Remove from Multiple Entries List</a></span></span></span>' +
				'</button>');
				$j('#remove_from_multiple_entries_list_' + p_iProductId).fadeIn();
			});
			
			
			
			return false;
		}
	});
	
	return false;
	
};

Reea.Entry.Grid.removeFromMultipleEntriesList = function(p_iProductId) {
	
	$j.ajax({
		url: Reea.Entry.Grid.removeFromListUrl,
		type: 'POST',
		data: {
			id: p_iProductId,
			form_key: Reea.Entry.Grid.formKey
		},
		dataType: 'json',
		success: function (p_oJSON) {
			
			var l_oContainer = $j('#remove_from_multiple_entries_list_' + p_iProductId).parent();
			$j('#remove_from_multiple_entries_list_' + p_iProductId).fadeOut(function() {
				$j(this).remove();
				$j(l_oContainer).append('<button style="display: none;" class="add-to-list scalable navigation" type="button" title="Add to Multiple Entries List" id="add_to_multiple_entries_list_' + p_iProductId + '">' + 
				'<span><span><span><a href="javascript: void(0);">Add to Multiple Entries List</a></span></span></span>' +
				'</button>');
				$j('#add_to_multiple_entries_list_' + p_iProductId).fadeIn();
			});
			
			return false;
			
		}
	});
	
	return false;
	
};

Reea.Entry.Grid.sortBy = function(p_sSortBy, p_oGrid) {
	
	var l_aTemp = p_sSortBy.split('|');
	var l_sDirection  = l_aTemp.pop();
	var l_sAttribute = l_aTemp.pop();
	
	console.log(l_sAttribute + ' ' + l_sDirection);
	
	p_oGrid.addVarToUrl(p_oGrid.sortVar, l_sAttribute);
	p_oGrid.addVarToUrl(p_oGrid.dirVar, l_sDirection);
	p_oGrid.reload(p_oGrid.url);
	
	return false;
};

Reea.EmailToAFriend = Reea.EmailToAFriend || {};

Reea.EmailToAFriend.viewList = function() {	
	
	$j.ajax({
		url: Reea.EmailToAFriend.viewListUrl,
		type: 'GET',
		success: function (p_sHTML) {
			$j('body').append('<div id="dialog-container"></div>');
			$j('#dialog-container').html(p_sHTML).dialog({
				title: 'Entries that will be added to the email',
				modal: true,
				resizable: false,
				width: 500,
				//height: 500,
				draggable: true,
				buttons: {
					'Close': function() {
						$j(this).dialog( "close" );
					}
				}
			});
			return false;
		}
	});
		
	return false;
};

Reea.EmailToAFriend.addToEmailEntriesList = function(p_iProductId) {
	
	$j.ajax({
		url: Reea.EmailToAFriend.addToListUrl,
		type: 'POST',
		data: {
			id: p_iProductId,
			form_key: Reea.Entry.Grid.formKey
		},
		dataType: 'json',
		success: function (p_oJSON) {
			
			var l_oContainer = $j('#add_to_email_entries_list_' + p_iProductId).parent();
			$j('#add_to_email_entries_list_' + p_iProductId).fadeOut(function() {
				$j(this).remove();
				$j(l_oContainer).append('<button style="display: none;" class="remove-from-email-list scalable delete" type="button" title="Remove from Email Entries List" id="remove_from_email_entries_list_' + p_iProductId + '">' + 
				'<span><span><span><a href="javascript: void(0);">Remove from Email Entries List</a></span></span></span>' +
				'</button>');
				$j('#remove_from_email_entries_list_' + p_iProductId).fadeIn();
			});
			
			
			
			return false;
		}
	});
	
	return false;
	
};

Reea.EmailToAFriend.counter = 1;

Reea.EmailToAFriend.removeFromEmailEntriesList = function(p_iProductId) {
	
	$j.ajax({
		url: Reea.EmailToAFriend.removeFromListUrl,
		type: 'POST',
		data: {
			id: p_iProductId,
			form_key: Reea.Entry.Grid.formKey
		},
		dataType: 'json',
		success: function (p_oJSON) {
			
			var l_oContainer = $j('#remove_from_email_entries_list_' + p_iProductId).parent();
			$j('#remove_from_email_entries_list_' + p_iProductId).fadeOut(function() {
				$j(this).remove();
				$j(l_oContainer).append('<button style="display: none;" class="add-to-email-list scalable navigation" type="button" title="Add to Email Entries List" id="add_to_email_entries_list_' + p_iProductId + '">' + 
				'<span><span><span><a href="javascript: void(0);">Add to Email Entries List</a></span></span></span>' +
				'</button>');
				$j('#add_to_email_entries_list_' + p_iProductId).fadeIn();
			});
			
			return false;
			
		}
	});
	
	return false;
	
};

Reea.EmailToAFriend.removeFromEmailEntriesListPopup = function(p_iProductId) {
	
	$j.ajax({
		url: Reea.EmailToAFriend.removeFromListUrl,
		type: 'POST',
		data: {
			id: p_iProductId,
			form_key: Reea.Entry.Grid.formKey
		},
		dataType: 'json',
		success: function (p_oJSON) {
			
			$j('#list_email_entry_' + p_iProductId).fadeOut(function() {
				$j(this).remove();
			});
			
			var l_oContainer = $j('#remove_from_email_entries_list_' + p_iProductId);
			
			if (l_oContainer.length) {
			
				var l_oContainer = l_oContainer.parent();
				
				$j('#remove_from_email_entries_list_' + p_iProductId).fadeOut(function() {
					$j('#remove_from_email_entries_list_' + p_iProductId).remove();
					$j(l_oContainer).append('<button style="display: none;" class="add-to-email-list scalable navigation" type="button" title="Add to Email Entries List" id="add_to_email_entries_list_' + p_iProductId + '">' + 
					'<span><span><span><a href="javascript: void(0);">Add to Email Entries List</a></span></span></span>' +
					'</button>');
					$j('#add_to_email_entries_list_' + p_iProductId).fadeIn();
					if (!$j('ul.email-entries-list li').length) {
						$j('#dialog-container').dialog('close');
					}
				});
			}
			
			return false;
			
		}
	});
	
	return false;
	
};

$j(document).ready(function() {
	
	$j('button.add-to-list').live('click', function(p_oEvent) {

		p_oEvent.stopPropagation();
		
		var l_iEntryId = $j(this).attr('id').split('_').pop();
		Reea.Entry.Grid.addToMultipleEntriesList(l_iEntryId);		
		
		
		return false;
	});
	
	$j('button.remove-from-list').live('click', function(p_oEvent) {

		p_oEvent.stopPropagation();
		
		var l_iEntryId = $j(this).attr('id').split('_').pop();
		Reea.Entry.Grid.removeFromMultipleEntriesList(l_iEntryId);
		
		return false;
	});
	
	$j('button.add-to-email-list').live('click', function(p_oEvent) {
		
		//alert('add-to-email-list');

		p_oEvent.stopPropagation();
		
		var l_iEntryId = $j(this).attr('id').split('_').pop();
		Reea.EmailToAFriend.addToEmailEntriesList(l_iEntryId);		
		
		
		return false;
	});
	
	$j('button.remove-from-email-list').live('click', function(p_oEvent) {
		
		//alert('remove-from-email-list');

		p_oEvent.stopPropagation();
		
		var l_iEntryId = $j(this).attr('id').split('_').pop();
		Reea.EmailToAFriend.removeFromEmailEntriesList(l_iEntryId);
		
		return false;
	});
});
