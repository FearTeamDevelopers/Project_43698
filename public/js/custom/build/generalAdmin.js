jQuery.noConflict();jQuery(document).ready(function(){jQuery('.widgetlist a').hover(function(){jQuery(this).switchClass('default','hover');},function(){jQuery(this).switchClass('hover','default');});jQuery(window).load(function(){jQuery("#loader, .loader").hide();jQuery.post('/admin/system/showprofiler/',function(msg){jQuery('body').append(msg);});});jQuery("[title]").tooltip({position:{my:"left top",at:"right+5 top-5"}});jQuery('#tabs, .tabs').tabs();jQuery('#image-cropper, #image-cropper2').cropit({imageBackground:true,imageBackgroundBorderSize:15});jQuery('.cropit-form').submit(function(){var hi=jQuery(this).find('.cropit-hidden-resized-image');var a=jQuery('#image-cropper').cropit('export');hi.val(a);return true;});jQuery('.cropit-form-dual').submit(function(){var hi=jQuery(this).find('.cropit-hidden-resized-image');var hi2=jQuery(this).find('.cropit-hidden-resized-image2');var a=jQuery('#image-cropper').cropit('export');var b=jQuery('#image-cropper2').cropit('export');hi.val(a);hi2.val(b);return true;});jQuery('.datepicker, .datepicker2, .datepicker3').datepicker({changeMonth:true,changeYear:true,dateFormat:'yy-mm-dd',firstDay:1});jQuery('button.dialog, a.dialog').click(function(){var href=jQuery(this).attr('href');var val=jQuery(this).attr('value');jQuery('#dialog p').load(href);jQuery('#dialog').dialog({title:val,width:600,modal:true,position:{my:'center',at:'top',of:window},buttons:{Close:function(){jQuery('#dialog p').text('');jQuery(this).dialog('close');}}});return false;});jQuery.fn.dataTableExt.oApi.fnPagingInfo=function(oSettings)
{return{"iStart":oSettings._iDisplayStart,"iEnd":oSettings.fnDisplayEnd(),"iLength":oSettings._iDisplayLength,"iTotal":oSettings.fnRecordsTotal(),"iFilteredTotal":oSettings.fnRecordsDisplay(),"iPage":Math.ceil(oSettings._iDisplayStart/oSettings._iDisplayLength),"iTotalPages":Math.ceil(oSettings.fnRecordsDisplay()/oSettings._iDisplayLength)};};jQuery('.stdtable').DataTable({'aaSorting':[],'iDisplayLength':25,'sPaginationType':'full_numbers'});var selected=[];var type=jQuery('#type').val();var table=jQuery('.stdtable2').DataTable({'aaSorting':[],'iDisplayLength':50,'sPaginationType':'full_numbers',"serverSide":true,"bProcessing":true,"sServerMethod":"POST","sAjaxDataProp":"data","sAjaxSource":"/admin/"+type+"/load/","fnServerParams":function(aoData){aoData.push({"name":"page","value":this.fnPagingInfo().iPage});},"rowCallback":function(row,data,displayIndex){if(jQuery.inArray(data[0],selected)!==-1){jQuery(row).addClass('togglerow');}},"aoColumns":[null,null,null,null,{"bSortable":false},{"bSortable":false},{"bSortable":false}]});table.on('draw',function(){jQuery('.ajaxDelete').click(function(event){event.preventDefault();var parentTr=jQuery(this).parents('tr');var url=jQuery(this).attr('href');var csrf=jQuery('#csrf').val();jQuery('#deleteDialog p').text('Opravdu chcete pokračovat v mazání?');jQuery('#deleteDialog').dialog({resizable:false,width:300,height:150,modal:true,buttons:{"Smazat":function(){jQuery("#loader, .loader").show();jQuery.post(url,{csrf:csrf},function(data){if(data.error==false){jQuery("#loader, .loader").hide();jQuery('#csrf').val(data.csrf);parentTr.fadeOut();}else{alert(data.message);}});jQuery(this).dialog("close");},"Zrušit":function(){jQuery(this).dialog("close");}}});return false;});jQuery('.ajaxReload').click(function(event){event.preventDefault();var url=jQuery(this).attr('href');var csrf=jQuery('#csrf').val();jQuery('#deleteDialog p').text('Opravdu chcete pokračovat?');jQuery('#deleteDialog').dialog({resizable:false,width:300,height:150,modal:true,buttons:{"Ano":function(){jQuery("#loader, .loader").show();jQuery.post(url,{csrf:csrf},function(data){if(data.error==false){jQuery('#csrf').val(data.csrf);location.reload();}else{alert(data.message);}});},"Ne":function(){jQuery(this).dialog("close");}}});return false;});jQuery('button.dialog, a.dialog').click(function(){var href=jQuery(this).attr('href');var val=jQuery(this).attr('value');jQuery('#dialog p').load(href);jQuery('#dialog').dialog({title:val,width:600,modal:true,position:{my:'center',at:'top',of:window},buttons:{Close:function(){jQuery('#dialog p').text('');jQuery(this).dialog('close');}}});return false;});});jQuery('.stdtable2 tbody').on('click','tr',function(){var id=jQuery(this).find('td:first').text();var index=jQuery.inArray(id,selected);if(index===-1){selected.push(id);}else{selected.splice(index,1);}
jQuery(this).toggleClass('togglerow');});jQuery('.tableoptions select').change(function(){var val=jQuery(this).children('option:selected').val();var name=jQuery(this).attr('name');jQuery('.tableoptions select[name='+name+']').val(val);if(val==2){var tr=jQuery('.stdtable2 tbody tr');tr.each(function(){var id=jQuery(this).find('td:first').text();var index=jQuery.inArray(id,selected);if(index===-1){selected.push(id);}
jQuery(this).addClass('togglerow');});}else if(val==1){jQuery('.stdtable2 tbody tr.togglerow').removeClass('togglerow');selected=[];}});jQuery('.ajax-massaction').click(function(event){event.preventDefault();jQuery("#loader, .loader").show();var url=jQuery(this).attr('href');var action=jQuery('.tableoptions select[name=action]').children('option:selected').val();var csrf=jQuery('#csrf').val();jQuery.post(url,{csrf:csrf,action:action,ids:selected},function(data){jQuery('#dialog p').text(data.message);jQuery('#csrf').val(data.csrf);jQuery('#dialog').dialog({title:'Výsledek',width:450,modal:true,buttons:{Close:function(){jQuery(this).dialog('close');jQuery('.stdtable2 tbody tr.togglerow').removeClass('togglerow');jQuery('.tableoptions select[name=selection]').val('1');selected=[];table.ajax.reload();jQuery("#loader, .loader").hide();}}});});return false;});jQuery('.userinfo').click(function(){if(!jQuery(this).hasClass('userinfodrop')){var t=jQuery(this);jQuery('.userdrop').width(t.width()+30);jQuery('.userdrop').slideDown('fast');t.addClass('userinfodrop');}else{jQuery(this).removeClass('userinfodrop');jQuery('.userdrop').hide();}
jQuery('.notialert').removeClass('notiactive');jQuery('.notibox').hide();return false;});jQuery('.notialert').click(function(){var t=jQuery(this);var url=t.attr('href');if(!t.hasClass('notiactive')){jQuery('.notibox').slideDown('fast');jQuery('.noticontent').empty();jQuery('.notibox .tabmenu li').each(function(){jQuery(this).removeClass('current');});jQuery('.notibox .tabmenu li:first-child').addClass('current');t.addClass('notiactive');jQuery('.notibox .loader').show();jQuery.post(url,function(data){jQuery('.notibox .loader').hide();jQuery('.noticontent').append(data);});}else{t.removeClass('notiactive');jQuery('.notibox').hide();}
jQuery('.userinfo').removeClass('userinfodrop');jQuery('.userdrop').hide();return false;});jQuery(document).click(function(event){var ud=jQuery('.userdrop');var nb=jQuery('.notibox');if(!jQuery(event.target).is('.userdrop')&&ud.is(':visible')){ud.hide();jQuery('.userinfo').removeClass('userinfodrop');}
if(!jQuery(event.target).is('.notibox')&&nb.is(':visible')){nb.hide();jQuery('.notialert').removeClass('notiactive');}});jQuery('.tabmenu a').click(function(){var url=jQuery(this).attr('href');jQuery('.tabmenu li').each(function(){jQuery(this).removeClass('current');});jQuery('.noticontent').empty();jQuery('.notibox .loader').show();jQuery(this).parent().addClass('current');jQuery.post(url,function(data){jQuery('.notibox .loader').hide();jQuery('.noticontent').append(data);});return false;});jQuery('.widgetbox .title').hover(function(){if(!jQuery(this).parent().hasClass('uncollapsible'))
jQuery(this).addClass('titlehover');},function(){jQuery(this).removeClass('titlehover');});jQuery('.widgetbox .title').click(function(){if(!jQuery(this).parent().hasClass('uncollapsible')){if(jQuery(this).next().is(':visible')){jQuery(this).next().slideUp('fast');jQuery(this).addClass('widgettoggle');}else{jQuery(this).next().slideDown('fast');jQuery(this).removeClass('widgettoggle');}}});jQuery('.leftmenu a span').each(function(){jQuery(this).wrapInner('<em />');});jQuery('.leftmenu a').click(function(e){var t=jQuery(this);var p=t.parent();var ul=p.find('ul');var li=jQuery(this).parents('.lefticon');if(jQuery(this).hasClass('menudrop')){if(!li.length>0){if(ul.length>0){if(ul.is(':visible')){ul.slideUp('fast');p.next().css({borderTop:'0'});t.removeClass('active');}else{ul.slideDown('fast');p.next().css({borderTop:'1px solid #ddd'});t.addClass('active');}}
if(jQuery(e.target).is('em'))
return true;else
return false;}else{return true;}}else{return true;}});jQuery('.leftmenu a').hover(function(){if(jQuery(this).parents('.lefticon').length>0){jQuery(this).next().stop(true,true).fadeIn();}},function(){if(jQuery(this).parents('.lefticon').length>0){jQuery(this).next().stop(true,true).fadeOut();}});jQuery('#togglemenuleft a').click(function(){if(jQuery('.mainwrapper').hasClass('lefticon')){jQuery('.mainwrapper').removeClass('lefticon');jQuery(this).removeClass('toggle');jQuery('.leftmenu a').each(function(){jQuery(this).next().remove();});}else{jQuery('.mainwrapper').addClass('lefticon');jQuery(this).addClass('toggle');showTooltipLeftMenu();}});function showTooltipLeftMenu(){jQuery('.leftmenu a').each(function(){var text=jQuery(this).text();jQuery(this).removeClass('active');jQuery(this).parent().attr('style','');jQuery(this).parent().find('ul').hide();jQuery(this).after('<div class="menutip">'+text+'</div>');});}
jQuery(document).scroll(function(){var pos=jQuery(document).scrollTop();if(pos>50){jQuery('.floatleft').css({position:'fixed',top:'10px',right:'10px'});}else{jQuery('.floatleft').css({position:'absolute',top:0,right:0});}});jQuery(document).scroll(function(){if(jQuery(this).width()>580){var pos=jQuery(document).scrollTop();if(pos>50){jQuery('.floatright').css({position:'fixed',top:'10px',right:'10px'});}else{jQuery('.floatright').css({position:'absolute',top:0,right:0});}}});jQuery('.notification .close').click(function(){jQuery(this).parent().fadeOut();});jQuery('.errorWrapper a').hover(function(){jQuery(this).switchClass('default','hover');},function(){jQuery(this).switchClass('hover','default');});var TO=false;jQuery(window).resize(function(){if(jQuery(this).width()<1024){jQuery('.mainwrapper').addClass('lefticon');jQuery('#togglemenuleft').hide();jQuery('.mainright').insertBefore('.footer');showTooltipLeftMenu();if(jQuery(this).width()<=580){jQuery('.stdtable, .stdtable2').wrap('<div class="tablewrapper"></div>');if(jQuery('.headerinner2').length==0)
insertHeaderInner2();}else{removeHeaderInner2();}}else{toggleLeftMenu();removeHeaderInner2();}});if(jQuery(window).width()<1024){jQuery('.mainwrapper').addClass('lefticon');jQuery('#togglemenuleft').hide();jQuery('.mainright').insertBefore('.footer');showTooltipLeftMenu();if(jQuery(window).width()<=580){jQuery('table').wrap('<div class="tablewrapper"></div>');insertHeaderInner2();}}else{toggleLeftMenu();}
function toggleLeftMenu(){if(!jQuery('.mainwrapper').hasClass('lefticon')){jQuery('.mainwrapper').removeClass('lefticon');jQuery('#togglemenuleft').show();}else{jQuery('#togglemenuleft').show();jQuery('#togglemenuleft a').addClass('toggle');}}
function insertHeaderInner2(){jQuery('.headerinner').after('<div class="headerinner2"></div>');jQuery('#searchPanel').appendTo('.headerinner2');jQuery('#userPanel').appendTo('.headerinner2');jQuery('#userPanel').addClass('userinfomenu');}
function removeHeaderInner2(){jQuery('#searchPanel').insertBefore('#notiPanel');jQuery('#userPanel').insertAfter('#notiPanel');jQuery('#userPanel').removeClass('userinfomenu');jQuery('.headerinner2').remove();}
jQuery('.uploadForm .multi_upload').click(function(){if(jQuery('.uploadForm .file_inputs input[type=file]').length<7){jQuery('.uploadForm .file_inputs input[type=file]').last().after('<input type="file" name="uploadfile[]" accept="image/*"/>');}});jQuery('.uploadForm .multi_upload_dec').click(function(){if(jQuery('.uploadForm .file_inputs input[type=file]').length>1){jQuery('.uploadForm .file_inputs input[type=file]').last().remove();}});jQuery('.uploadForm').submit(function(){jQuery('#loader').show();});jQuery('.deleteImg').click(function(event){event.preventDefault();var url=jQuery(this).attr('href');var csrf=jQuery('#csrf').val();jQuery.post(url,{csrf:csrf},function(data){if(data.error==false){jQuery('#csrf').val(data.csrf);jQuery('#currentLogo, #currentImage').hide(500);jQuery('.uploadNewImage').removeClass('nodisplay');}else{jQuery('#currentLogo, #currentImage').append("<label class='error'>"+data.message+"</label>")}});return false;});jQuery('.imagelist a.delete').click(function(event){event.preventDefault();var parent=jQuery(this).parents('li');var url=jQuery(this).attr('href');var csrf=jQuery('#csrf').val();jQuery('#deleteDialog p').text('Opravdu chcete pokračovat v mazání?');jQuery('#deleteDialog').dialog({resizable:false,width:300,height:150,modal:true,buttons:{"Smazat":function(){jQuery("#loader, .loader").show();jQuery.post(url,{csrf:csrf},function(data){if(data.error==false){jQuery('#csrf').val(data.csrf);jQuery("#loader, .loader").hide();parent.hide('explode',500);}else{alert(data.message);}});jQuery(this).dialog("close");},"Zrušit":function(){jQuery(this).dialog("close");}}});return false;});jQuery('.imagelist a.activate').click(function(event){event.preventDefault();var parent=jQuery(this).parents('li');var url=jQuery(this).attr('href');var csrf=jQuery('#csrf').val();jQuery.post(url,{csrf:csrf},function(data){jQuery('#csrf').val(data.csrf);if(data.status=='active'){parent.removeClass('photoinactive').addClass('photoactive');}else if(data.status=='inactive'){parent.removeClass('photoactive').addClass('photoinactive');}else{alert(data.message);}});return false;});jQuery('.ajaxDelete').click(function(event){event.preventDefault();var parentTr=jQuery(this).parents('tr');var url=jQuery(this).attr('href');var csrf=jQuery('#csrf').val();jQuery('#deleteDialog p').text('Opravdu chcete pokračovat v mazání?');jQuery('#deleteDialog').dialog({resizable:false,width:300,height:150,modal:true,buttons:{"Smazat":function(){jQuery("#loader, .loader").show();jQuery.post(url,{csrf:csrf},function(data){if(data.error==false){jQuery('#csrf').val(data.csrf);jQuery("#loader, .loader").hide();parentTr.fadeOut();}else{alert(data.message);}});jQuery(this).dialog("close");},"Zrušit":function(){jQuery(this).dialog("close");}}});return false;});jQuery('.ajaxReload').click(function(){event.preventDefault();var url=jQuery(this).attr('href');var csrf=jQuery('#csrf').val();jQuery('#deleteDialog p').text('Opravdu chcete pokračovat?');jQuery('#deleteDialog').dialog({resizable:false,width:300,height:150,modal:true,buttons:{"Ano":function(){jQuery("#loader, .loader").show();jQuery.post(url,{csrf:csrf},function(data){if(data.error==false){jQuery('#csrf').val(data.csrf);location.reload();}else{alert(data.message);}});},"Ne":function(){jQuery(this).dialog("close");}}});return false;});jQuery('.ajaxChangestate').click(function(){var url=jQuery(this).attr('href');var csrf=jQuery('#csrf').val();jQuery("#loader, .loader").show();jQuery.post(url,{csrf:csrf},function(data){if(data.error==false){jQuery('#csrf').val(data.csrf);location.reload();}else{alert(data.message);}});return false;});jQuery('.imagelist img').hover(function(){jQuery(this).stop().animate({opacity:0.75});},function(){jQuery(this).stop().animate({opacity:1});});jQuery('.btn').hover(function(){jQuery(this).stop().animate({backgroundColor:'#eee'});},function(){jQuery(this).stop().animate({backgroundColor:'#f7f7f7'});});jQuery('.stdbtn').hover(function(){jQuery(this).stop().animate({opacity:0.75});},function(){jQuery(this).stop().animate({opacity:1});});jQuery('.button-edit').button({icons:{primary:'ui-icon-pencil'},text:false});jQuery('.button-delete').button({icons:{primary:'ui-icon-trash'},text:false});jQuery('.button-detail').button({icons:{primary:'ui-icon-search'},text:false});jQuery('.button-comment').button({icons:{primary:'ui-icon-comment'},text:false});jQuery('.button-person').button({icons:{primary:'ui-icon-person'},text:false});jQuery('.stdtable .checkall').click(function(){var parentTable=jQuery(this).parents('table');var ch=parentTable.find('tbody input[type=checkbox]');if(jQuery(this).is(':checked')){ch.each(function(){jQuery(this).attr('checked',true);jQuery(this).parent().addClass('checked');jQuery(this).parents('tr').addClass('selected');});parentTable.find('.checkall').each(function(){jQuery(this).attr('checked',true);});}else{ch.each(function(){jQuery(this).attr('checked',false);jQuery(this).parent().removeClass('checked');jQuery(this).parents('tr').removeClass('selected');});parentTable.find('.checkall').each(function(){jQuery(this).attr('checked',false);});}});jQuery('.stdtable tbody input[type=checkbox]').click(function(){if(jQuery(this).is(':checked')){jQuery(this).parents('tr').addClass('selected');}else{jQuery(this).parents('tr').removeClass('selected');}});jQuery('.massActionForm').submit(function(){var sel=false;var ch=jQuery(this).find('tbody input[type=checkbox]');ch.each(function(){if(jQuery(this).is(':checked')){sel=true;}});if(!sel){alert('No data selected');return false;}else{return true;}});jQuery('input[type=checkbox]').each(function(){var t=jQuery(this);t.wrap('<span class="checkbox"></span>');t.click(function(){if(jQuery(this).is(':checked')){t.attr('checked',true);t.parent().addClass('checked');}else{t.attr('checked',false);t.parent().removeClass('checked');}});if(jQuery(this).is(':checked')){t.attr('checked',true);t.parent().addClass('checked');}else{t.attr('checked',false);t.parent().removeClass('checked');}});});var editor1=CKEDITOR.replace('ckeditor',{height:550,filebrowserBrowseUrl:'/public/js/plugins/filemanager/elfinder.php',filebrowserImageBrowseUrl:'/public/js/plugins/filemanager/elfinder.php',filebrowserFlashBrowseUrl:'/public/js/plugins/filemanager/elfinder.php'});var editor2=CKEDITOR.replace('ckeditor2',{filebrowserBrowseUrl:'/public/js/plugins/filemanager/elfinder.php',filebrowserImageBrowseUrl:'/public/js/plugins/filemanager/elfinder.php',filebrowserFlashBrowseUrl:'/public/js/plugins/filemanager/elfinder.php'});