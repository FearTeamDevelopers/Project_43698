jQuery.noConflict(),jQuery(document).ready(function(){function e(){jQuery(".leftmenu a").each(function(){var e=jQuery(this).text();jQuery(this).removeClass("active"),jQuery(this).parent().attr("style",""),jQuery(this).parent().find("ul").hide(),jQuery(this).after('<div class="menutip">'+e+"</div>")})}function t(){jQuery(".mainwrapper").hasClass("lefticon")?(jQuery("#togglemenuleft").show(),jQuery("#togglemenuleft a").addClass("toggle")):(jQuery(".mainwrapper").removeClass("lefticon"),jQuery("#togglemenuleft").show())}function r(){jQuery(".headerinner").after('<div class="headerinner2"></div>'),jQuery("#searchPanel").appendTo(".headerinner2"),jQuery("#userPanel").appendTo(".headerinner2"),jQuery("#userPanel").addClass("userinfomenu")}function i(){jQuery("#searchPanel").insertBefore("#notiPanel"),jQuery("#userPanel").insertAfter("#notiPanel"),jQuery("#userPanel").removeClass("userinfomenu"),jQuery(".headerinner2").remove()}jQuery(".widgetlist a").hover(function(){jQuery(this).switchClass("default","hover")},function(){jQuery(this).switchClass("hover","default")}),jQuery(window).load(function(){jQuery("#loader, .loader").hide(),jQuery.post("/admin/system/showprofiler/",function(e){jQuery("body").append(e)})}),jQuery("[title]").tooltip({position:{my:"left top",at:"right+5 top-5"}}),jQuery("#tabs, .tabs").tabs(),jQuery("#image-cropper, #image-cropper2").cropit({imageBackground:!0,imageBackgroundBorderSize:15}),jQuery(".cropit-form").submit(function(){var e=jQuery(this).find(".cropit-hidden-resized-image"),t=jQuery("#image-cropper").cropit("export");return e.val(t),!0}),jQuery(".cropit-form-dual").submit(function(){var e=jQuery(this).find(".cropit-hidden-resized-image"),t=jQuery(this).find(".cropit-hidden-resized-image2"),r=jQuery("#image-cropper").cropit("export"),i=jQuery("#image-cropper2").cropit("export");return e.val(r),t.val(i),!0}),jQuery(".datepicker, .datepicker2, .datepicker3").datepicker({changeMonth:!0,changeYear:!0,dateFormat:"yy-mm-dd",firstDay:1}),jQuery("button.dialog, a.dialog").click(function(){var e=jQuery(this).attr("href"),t=jQuery(this).attr("value");return jQuery("#dialog p").load(e),jQuery("#dialog").dialog({title:t,width:600,modal:!0,position:{my:"center",at:"top",of:window},buttons:{Close:function(){jQuery("#dialog p").text(""),jQuery(this).dialog("close")}}}),!1}),jQuery.fn.dataTableExt.oApi.fnPagingInfo=function(e){return{iStart:e._iDisplayStart,iEnd:e.fnDisplayEnd(),iLength:e._iDisplayLength,iTotal:e.fnRecordsTotal(),iFilteredTotal:e.fnRecordsDisplay(),iPage:Math.ceil(e._iDisplayStart/e._iDisplayLength),iTotalPages:Math.ceil(e.fnRecordsDisplay()/e._iDisplayLength)}},jQuery(".stdtable").DataTable({aaSorting:[],iDisplayLength:25,sPaginationType:"full_numbers"});var a=[],o=jQuery("#type").val(),u=jQuery(".stdtable2").DataTable({aaSorting:[],iDisplayLength:50,sPaginationType:"full_numbers",serverSide:!0,bProcessing:!0,sServerMethod:"POST",sAjaxDataProp:"data",sAjaxSource:"/admin/"+o+"/load/",fnServerParams:function(e){e.push({name:"page",value:this.fnPagingInfo().iPage})},rowCallback:function(e,t,r){-1!==jQuery.inArray(t[0],a)&&jQuery(e).addClass("togglerow")},aoColumns:[null,null,null,null,{bSortable:!1},{bSortable:!1},{bSortable:!1}]});u.on("draw",function(){jQuery(".ajaxDelete").click(function(e){e.preventDefault();var t=jQuery(this).parents("tr"),r=jQuery(this).attr("href"),i=jQuery("#csrf").val();return jQuery("#deleteDialog p").text("Opravdu chcete pokračovat v mazání?"),jQuery("#deleteDialog").dialog({resizable:!1,width:300,height:150,modal:!0,buttons:{Smazat:function(){jQuery("#loader, .loader").show(),jQuery.post(r,{csrf:i},function(e){"success"==e?(jQuery("#loader, .loader").hide(),t.fadeOut()):alert(e)}),jQuery(this).dialog("close")},"Zrušit":function(){jQuery(this).dialog("close")}}}),!1}),jQuery(".ajaxReload").click(function(e){e.preventDefault();var t=jQuery(this).attr("href"),r=jQuery("#csrf").val();return jQuery("#deleteDialog p").text("Opravdu chcete pokračovat?"),jQuery("#deleteDialog").dialog({resizable:!1,width:300,height:150,modal:!0,buttons:{Ano:function(){jQuery("#loader, .loader").show(),jQuery.post(t,{csrf:r},function(e){"success"==e?location.reload():alert(e)})},Ne:function(){jQuery(this).dialog("close")}}}),!1}),jQuery("button.dialog, a.dialog").click(function(){var e=jQuery(this).attr("href"),t=jQuery(this).attr("value");return jQuery("#dialog p").load(e),jQuery("#dialog").dialog({title:t,width:600,modal:!0,position:{my:"center",at:"top",of:window},buttons:{Close:function(){jQuery("#dialog p").text(""),jQuery(this).dialog("close")}}}),!1})}),jQuery(".stdtable2 tbody").on("click","tr",function(){var e=jQuery(this).find("td:first").text(),t=jQuery.inArray(e,a);-1===t?a.push(e):a.splice(t,1),jQuery(this).toggleClass("togglerow")}),jQuery(".tableoptions select").change(function(){var e=jQuery(this).children("option:selected").val(),t=jQuery(this).attr("name");if(jQuery(".tableoptions select[name="+t+"]").val(e),2==e){var r=jQuery(".stdtable2 tbody tr");r.each(function(){var e=jQuery(this).find("td:first").text(),t=jQuery.inArray(e,a);-1===t&&a.push(e),jQuery(this).addClass("togglerow")})}else 1==e&&(jQuery(".stdtable2 tbody tr.togglerow").removeClass("togglerow"),a=[])}),jQuery(".ajax-massaction").click(function(e){e.preventDefault(),jQuery("#loader, .loader").show();var t=jQuery(this).attr("href"),r=jQuery(".tableoptions select[name=action]").children("option:selected").val(),i=jQuery("#csrf").val();return jQuery.post(t,{csrf:i,action:r,ids:a},function(e){jQuery("#dialog p").text(e),jQuery("#dialog").dialog({title:"Výsledek",width:450,modal:!0,buttons:{Close:function(){jQuery(this).dialog("close"),jQuery(".stdtable2 tbody tr.togglerow").removeClass("togglerow"),jQuery(".tableoptions select[name=selection]").val("1"),a=[],u.ajax.reload(),jQuery("#loader, .loader").hide()}}})}),!1}),jQuery(".userinfo").click(function(){if(jQuery(this).hasClass("userinfodrop"))jQuery(this).removeClass("userinfodrop"),jQuery(".userdrop").hide();else{var e=jQuery(this);jQuery(".userdrop").width(e.width()+30),jQuery(".userdrop").slideDown("fast"),e.addClass("userinfodrop")}return jQuery(".notialert").removeClass("notiactive"),jQuery(".notibox").hide(),!1}),jQuery(".notialert").click(function(){var e=jQuery(this),t=e.attr("href");return e.hasClass("notiactive")?(e.removeClass("notiactive"),jQuery(".notibox").hide()):(jQuery(".notibox").slideDown("fast"),jQuery(".noticontent").empty(),jQuery(".notibox .tabmenu li").each(function(){jQuery(this).removeClass("current")}),jQuery(".notibox .tabmenu li:first-child").addClass("current"),e.addClass("notiactive"),jQuery(".notibox .loader").show(),jQuery.post(t,function(e){jQuery(".notibox .loader").hide(),jQuery(".noticontent").append(e)})),jQuery(".userinfo").removeClass("userinfodrop"),jQuery(".userdrop").hide(),!1}),jQuery(document).click(function(e){var t=jQuery(".userdrop"),r=jQuery(".notibox");!jQuery(e.target).is(".userdrop")&&t.is(":visible")&&(t.hide(),jQuery(".userinfo").removeClass("userinfodrop")),!jQuery(e.target).is(".notibox")&&r.is(":visible")&&(r.hide(),jQuery(".notialert").removeClass("notiactive"))}),jQuery(".tabmenu a").click(function(){var e=jQuery(this).attr("href");return jQuery(".tabmenu li").each(function(){jQuery(this).removeClass("current")}),jQuery(".noticontent").empty(),jQuery(".notibox .loader").show(),jQuery(this).parent().addClass("current"),jQuery.post(e,function(e){jQuery(".notibox .loader").hide(),jQuery(".noticontent").append(e)}),!1}),jQuery(".widgetbox .title").hover(function(){jQuery(this).parent().hasClass("uncollapsible")||jQuery(this).addClass("titlehover")},function(){jQuery(this).removeClass("titlehover")}),jQuery(".widgetbox .title").click(function(){jQuery(this).parent().hasClass("uncollapsible")||(jQuery(this).next().is(":visible")?(jQuery(this).next().slideUp("fast"),jQuery(this).addClass("widgettoggle")):(jQuery(this).next().slideDown("fast"),jQuery(this).removeClass("widgettoggle")))}),jQuery(".leftmenu a span").each(function(){jQuery(this).wrapInner("<em />")}),jQuery(".leftmenu a").click(function(e){var t=jQuery(this),r=t.parent(),i=r.find("ul"),a=jQuery(this).parents(".lefticon");return jQuery(this).hasClass("menudrop")&&!a.length>0?(i.length>0&&(i.is(":visible")?(i.slideUp("fast"),r.next().css({borderTop:"0"}),t.removeClass("active")):(i.slideDown("fast"),r.next().css({borderTop:"1px solid #ddd"}),t.addClass("active"))),jQuery(e.target).is("em")?!0:!1):!0}),jQuery(".leftmenu a").hover(function(){jQuery(this).parents(".lefticon").length>0&&jQuery(this).next().stop(!0,!0).fadeIn()},function(){jQuery(this).parents(".lefticon").length>0&&jQuery(this).next().stop(!0,!0).fadeOut()}),jQuery("#togglemenuleft a").click(function(){jQuery(".mainwrapper").hasClass("lefticon")?(jQuery(".mainwrapper").removeClass("lefticon"),jQuery(this).removeClass("toggle"),jQuery(".leftmenu a").each(function(){jQuery(this).next().remove()})):(jQuery(".mainwrapper").addClass("lefticon"),jQuery(this).addClass("toggle"),e())}),jQuery(document).scroll(function(){var e=jQuery(document).scrollTop();e>50?jQuery(".floatleft").css({position:"fixed",top:"10px",right:"10px"}):jQuery(".floatleft").css({position:"absolute",top:0,right:0})}),jQuery(document).scroll(function(){if(jQuery(this).width()>580){var e=jQuery(document).scrollTop();e>50?jQuery(".floatright").css({position:"fixed",top:"10px",right:"10px"}):jQuery(".floatright").css({position:"absolute",top:0,right:0})}}),jQuery(".notification .close").click(function(){jQuery(this).parent().fadeOut()}),jQuery(".errorWrapper a").hover(function(){jQuery(this).switchClass("default","hover")},function(){jQuery(this).switchClass("hover","default")});jQuery(window).resize(function(){jQuery(this).width()<1024?(jQuery(".mainwrapper").addClass("lefticon"),jQuery("#togglemenuleft").hide(),jQuery(".mainright").insertBefore(".footer"),e(),jQuery(this).width()<=580?(jQuery(".stdtable, .stdtable2").wrap('<div class="tablewrapper"></div>'),0==jQuery(".headerinner2").length&&r()):i()):(t(),i())}),jQuery(window).width()<1024?(jQuery(".mainwrapper").addClass("lefticon"),jQuery("#togglemenuleft").hide(),jQuery(".mainright").insertBefore(".footer"),e(),jQuery(window).width()<=580&&(jQuery("table").wrap('<div class="tablewrapper"></div>'),r())):t(),jQuery(".uploadForm .multi_upload").click(function(){jQuery(".uploadForm .file_inputs input[type=file]").length<7&&jQuery(".uploadForm .file_inputs input[type=file]").last().after('<input type="file" name="uploadfile[]" accept="image/*"/>')}),jQuery(".uploadForm .multi_upload_dec").click(function(){jQuery(".uploadForm .file_inputs input[type=file]").length>1&&jQuery(".uploadForm .file_inputs input[type=file]").last().remove()}),jQuery(".uploadForm").submit(function(){jQuery("#loader").show()}),jQuery(".deleteImg").click(function(e){e.preventDefault();var t=jQuery(this).attr("href"),r=jQuery("#csrf").val();return jQuery.post(t,{csrf:r},function(e){"success"==e?(jQuery("#currentLogo, #currentImage").hide(500),jQuery(".uploadNewImage").removeClass("nodisplay")):jQuery("#currentLogo").append("<label class='error'>"+e+"</label>")}),!1}),jQuery(".imagelist a.delete").click(function(e){e.preventDefault();var t=jQuery(this).parents("li"),r=jQuery(this).attr("href"),i=jQuery("#csrf").val();return jQuery("#deleteDialog p").text("Opravdu chcete pokračovat v mazání?"),jQuery("#deleteDialog").dialog({resizable:!1,width:300,height:150,modal:!0,buttons:{Smazat:function(){jQuery("#loader, .loader").show(),jQuery.post(r,{csrf:i},function(e){"success"==e?(jQuery("#loader, .loader").hide(),t.hide("explode",500)):alert(e)}),jQuery(this).dialog("close")},"Zrušit":function(){jQuery(this).dialog("close")}}}),!1}),jQuery(".imagelist a.activate").click(function(e){e.preventDefault();var t=jQuery(this).parents("li"),r=jQuery(this).attr("href"),i=jQuery("#csrf").val();return jQuery.post(r,{csrf:i},function(e){"active"==e?t.removeClass("photoinactive").addClass("photoactive"):"inactive"==e?t.removeClass("photoactive").addClass("photoinactive"):alert(e)}),!1}),jQuery(".ajaxDelete").click(function(e){e.preventDefault();var t=jQuery(this).parents("tr"),r=jQuery(this).attr("href"),i=jQuery("#csrf").val();return jQuery("#deleteDialog p").text("Opravdu chcete pokračovat v mazání?"),jQuery("#deleteDialog").dialog({resizable:!1,width:300,height:150,modal:!0,buttons:{Smazat:function(){jQuery("#loader, .loader").show(),jQuery.post(r,{csrf:i},function(e){"success"==e?(jQuery("#loader, .loader").hide(),t.fadeOut()):alert(e)}),jQuery(this).dialog("close")},"Zrušit":function(){jQuery(this).dialog("close")}}}),!1}),jQuery(".ajaxReload").click(function(){event.preventDefault();var e=jQuery(this).attr("href"),t=jQuery("#csrf").val();return jQuery("#deleteDialog p").text("Opravdu chcete pokračovat?"),jQuery("#deleteDialog").dialog({resizable:!1,width:300,height:150,modal:!0,buttons:{Ano:function(){jQuery("#loader, .loader").show(),jQuery.post(e,{csrf:t},function(e){"success"==e?location.reload():alert(e)})},Ne:function(){jQuery(this).dialog("close")}}}),!1}),jQuery(".ajaxChangestate").click(function(){var e=jQuery(this).attr("href"),t=jQuery("#csrf").val();return jQuery("#loader, .loader").show(),jQuery.post(e,{csrf:t},function(e){"active"==e||"inactive"==e?location.reload():alert(e)}),!1}),jQuery(".imagelist img").hover(function(){jQuery(this).stop().animate({opacity:.75})},function(){jQuery(this).stop().animate({opacity:1})}),jQuery(".btn").hover(function(){jQuery(this).stop().animate({backgroundColor:"#eee"})},function(){jQuery(this).stop().animate({backgroundColor:"#f7f7f7"})}),jQuery(".stdbtn").hover(function(){jQuery(this).stop().animate({opacity:.75})},function(){jQuery(this).stop().animate({opacity:1})}),jQuery(".button-edit").button({icons:{primary:"ui-icon-pencil"},text:!1}),jQuery(".button-delete").button({icons:{primary:"ui-icon-trash"},text:!1}),jQuery(".button-detail").button({icons:{primary:"ui-icon-search"},text:!1}),jQuery(".button-comment").button({icons:{primary:"ui-icon-comment"},text:!1}),jQuery(".button-person").button({icons:{primary:"ui-icon-person"},text:!1}),jQuery(".stdtable .checkall").click(function(){var e=jQuery(this).parents("table"),t=e.find("tbody input[type=checkbox]");jQuery(this).is(":checked")?(t.each(function(){jQuery(this).attr("checked",!0),jQuery(this).parent().addClass("checked"),jQuery(this).parents("tr").addClass("selected")}),e.find(".checkall").each(function(){jQuery(this).attr("checked",!0)})):(t.each(function(){jQuery(this).attr("checked",!1),jQuery(this).parent().removeClass("checked"),jQuery(this).parents("tr").removeClass("selected")}),e.find(".checkall").each(function(){jQuery(this).attr("checked",!1)}))}),jQuery(".stdtable tbody input[type=checkbox]").click(function(){jQuery(this).is(":checked")?jQuery(this).parents("tr").addClass("selected"):jQuery(this).parents("tr").removeClass("selected")}),jQuery(".massActionForm").submit(function(){var e=!1,t=jQuery(this).find("tbody input[type=checkbox]");return t.each(function(){jQuery(this).is(":checked")&&(e=!0)}),e?!0:(alert("No data selected"),!1)}),jQuery("input[type=checkbox]").each(function(){var e=jQuery(this);e.wrap('<span class="checkbox"></span>'),e.click(function(){jQuery(this).is(":checked")?(e.attr("checked",!0),e.parent().addClass("checked")):(e.attr("checked",!1),e.parent().removeClass("checked"))}),jQuery(this).is(":checked")?(e.attr("checked",!0),e.parent().addClass("checked")):(e.attr("checked",!1),e.parent().removeClass("checked"))})});var editor1=CKEDITOR.replace("ckeditor",{height:550,filebrowserBrowseUrl:"/public/js/plugins/filemanager/elfinder.php",filebrowserImageBrowseUrl:"/public/js/plugins/filemanager/elfinder.php",filebrowserFlashBrowseUrl:"/public/js/plugins/filemanager/elfinder.php"}),editor2=CKEDITOR.replace("ckeditor2",{filebrowserBrowseUrl:"/public/js/plugins/filemanager/elfinder.php",filebrowserImageBrowseUrl:"/public/js/plugins/filemanager/elfinder.php",filebrowserFlashBrowseUrl:"/public/js/plugins/filemanager/elfinder.php"});