jQuery.noConflict(),jQuery(document).ready(function(e){e(".showMenu").click(function(t){t.preventDefault(),e(this).closest(".navWrapper").find("nav").slideToggle(300).toggleClass("active")}),e("nav>ul>li>a.dropdown").click(function(t){t.preventDefault(),e(this).closest("li").hasClass("active")?e(this).closest("li").find("ul").slideUp(300,function(){e(this).closest("li").removeClass("active")}):e("nav ul li").hasClass("active")?(e("nav ul li.active").find("ul").slideUp(300).closest("li").removeClass("active"),e(this).closest("li").find("ul").slideDown(300,function(){e(this).closest("li").addClass("active")})):e(this).closest("li").find("ul").slideDown(300,function(){e(this).closest("li").addClass("active")})}),jQuery(window).load(function(){jQuery("#loader, .loader").hide(),jQuery.post("/app/system/showprofiler/",function(e){jQuery("body").append(e)})}),jQuery(".sendEmail").click(function(){"Odpovědět na Inzerát"==jQuery(".sendEmail span").text()?jQuery(".sendEmail span").text("Zavřít"):jQuery(".sendEmail span").text("Odpovědět na Inzerát"),jQuery("article").hasClass("arrow_box")?jQuery("article").removeClass("arrow_box"):jQuery("article").addClass("arrow_box"),jQuery("#sendEmail").toggle("slow")}),jQuery(".images a.dropdown").click(function(e){e.preventDefault(),jQuery(this).hide(),jQuery(".thumbImage").show("slow")}),jQuery(".sluzby").click(function(e){e.preventDefault(),jQuery("#dropdown").toggle("slow")}),jQuery(".closeNotif").click(function(e){e.preventDefault(),jQuery(".notificationWrapper").hide("slow")}),jQuery("#openEdit").click(function(e){e.preventDefault(),jQuery("#info").hide("slow"),jQuery("#edit").show("slow")}),jQuery("#closeEdit").click(function(e){e.preventDefault(),jQuery("#info").toggle("slow"),jQuery("#edit").toggle("slow")}),jQuery(".notificationWrapper .notification").is(":visible")&&setTimeout(function(){jQuery(".notificationWrapper .notification").hide("slow")},5e3),jQuery(".datepicker").datepicker({changeMonth:!0,changeYear:!0,dateFormat:"yy-mm-dd",firstDay:1}),jQuery(".ajax-button").click(function(){var e=jQuery(this).attr("href"),t=jQuery(this).val();jQuery("#dialog").load(e).dialog({title:t,width:"550px",modal:!0,position:{my:"center",at:"top",of:window},buttons:{Cancel:function(){jQuery(this).dialog("close")}}})}),jQuery(".uploadForm .multi_upload").click(function(){jQuery(".uploadForm .file_inputs input[type=file]").length<3&&jQuery(".uploadForm .file_inputs input[type=file]").last().after('<br/><input type="file" name="uploadfile[]" accept="image/*"/>')}),jQuery(".uploadForm .multi_upload_dec").click(function(){jQuery(".uploadForm .file_inputs input[type=file]").length>1&&(jQuery(".uploadForm .file_inputs input[type=file]").last().remove(),jQuery(".uploadForm .file_inputs br").last().remove())}),jQuery(".uploadForm").submit(function(){jQuery("#loader").show()}),jQuery(".ajaxDeleteImage").click(function(e){e.preventDefault();var t=jQuery(this),i=jQuery(this).attr("href"),r=jQuery("#csrf").val();return jQuery("#dialog p").text("Opravdu chcete pokračovat v mazání?"),jQuery("#dialog").dialog({resizable:!1,width:350,height:200,modal:!0,buttons:{Smazat:function(){jQuery("#loader, .loader").show(),jQuery.post(i,{csrf:r},function(e){"success"==e?t.parent("span").hide("explode",500):alert(e),jQuery("#loader, .loader").hide()}),jQuery(this).dialog("close")},"Zrušit":function(){jQuery(this).dialog("close")}}}),!1}),jQuery(".ajaxDelete").click(function(e){e.preventDefault();var t=jQuery(this).parents("article"),i=jQuery(this).attr("href"),r=jQuery("#csrf").val();return jQuery("#dialog p").text("Opravdu chcete pokračovat v mazání?"),jQuery("#dialog").dialog({resizable:!1,width:350,height:200,modal:!0,buttons:{Smazat:function(){jQuery("#loader, .loader").show(),jQuery.post(i,{csrf:r},function(e){"success"==e?t.fadeOut():alert(e),jQuery("#loader, .loader").hide()}),jQuery(this).dialog("close")},"Zrušit":function(){jQuery(this).dialog("close")}}}),!1}),jQuery(".ajaxReload").click(function(e){e.preventDefault();var t=jQuery(this).attr("href"),i=jQuery("#csrf").val();return jQuery("#dialog p").text("Opravdu chcete pokračovat?"),jQuery("#dialog").dialog({resizable:!1,width:350,height:200,modal:!0,buttons:{Ano:function(){jQuery.post(t,{csrf:i},function(e){"success"==e?location.reload():alert(e)})},Ne:function(){jQuery(this).dialog("close")}}}),!1}),jQuery(".ajaxChangestate").click(function(){var e=jQuery(this).attr("href"),t=jQuery("#csrf").val();return jQuery("#loader, .loader").show(),jQuery.post(e,{csrf:t},function(e){"active"==e||"inactive"==e?location.reload():alert(e),jQuery("#loader, .loader").hide()}),!1}),jQuery("#hledat").click(function(e){e.preventDefault(),jQuery(".search").submit()}),jQuery("#hledatHastrman").click(function(e){e.preventDefault(),jQuery(".fulltextsearch").submit()})});