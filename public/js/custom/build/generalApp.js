jQuery.noConflict();jQuery(document).ready(function(a){a(".showMenu").click(function(b){b.preventDefault();a(this).closest(".navWrapper").find("nav").slideToggle(300).toggleClass("active")});a("nav>ul>li>a.dropdown").click(function(b){b.preventDefault();if(a(this).closest("li").hasClass("active")){a(this).closest("li").find("ul").slideUp(300,function(){a(this).closest("li").removeClass("active")})}else{if(a("nav ul li").hasClass("active")){a("nav ul li.active").find("ul").slideUp(300).closest("li").removeClass("active");a(this).closest("li").find("ul").slideDown(300,function(){a(this).closest("li").addClass("active")})}else{a(this).closest("li").find("ul").slideDown(300,function(){a(this).closest("li").addClass("active")})}}});jQuery(window).load(function(){jQuery("#loader, .loader").hide();jQuery.post("/app/system/showprofiler/",function(b){jQuery("body").append(b)})});jQuery(".sendEmail").click(function(){if(jQuery(".sendEmail span").text()=="Odpovědět na Inzerát"){jQuery(".sendEmail span").text("Zavřít")}else{jQuery(".sendEmail span").text("Odpovědět na Inzerát")}if(jQuery("article").hasClass("arrow_box")){jQuery("article").removeClass("arrow_box")}else{jQuery("article").addClass("arrow_box")}jQuery("#sendEmail").toggle("slow")});jQuery(".images a.dropdown").click(function(b){b.preventDefault();jQuery(this).hide();jQuery(".thumbImage").show("slow")});jQuery(".sluzby").click(function(b){b.preventDefault();jQuery("#dropdown").toggle("slow")});jQuery(".closeNotif").click(function(b){b.preventDefault();jQuery(".notificationWrapper").hide("slow")});jQuery("#openEdit").click(function(b){b.preventDefault();jQuery("#info").hide("slow");jQuery("#edit").show("slow")});jQuery("#closeEdit").click(function(b){b.preventDefault();jQuery("#info").toggle("slow");jQuery("#edit").toggle("slow")});if(jQuery(".notificationWrapper .notification").is(":visible")){setTimeout(function(){jQuery(".notificationWrapper .notification").hide("slow")},10000)}jQuery(".datepicker").datepicker({changeMonth:true,changeYear:true,dateFormat:"yy-mm-dd",firstDay:1});jQuery(".ajax-button").click(function(){var b=jQuery(this).attr("href");var c=jQuery(this).val();jQuery("#dialog").load(b).dialog({title:c,width:"550px",modal:true,position:{my:"center",at:"top",of:window},buttons:{Cancel:function(){jQuery(this).dialog("close")}}})});jQuery(".uploadForm .multi_upload").click(function(){if(jQuery(".uploadForm .file_inputs input[type=file]").length<3){jQuery(".uploadForm .file_inputs input[type=file]").last().after('<br/><input type="file" name="uploadfile[]" accept="image/*"/>')}});jQuery(".uploadForm .multi_upload_dec").click(function(){if(jQuery(".uploadForm .file_inputs input[type=file]").length>1){jQuery(".uploadForm .file_inputs input[type=file]").last().remove();jQuery(".uploadForm .file_inputs br").last().remove()}});jQuery(".uploadForm").submit(function(){jQuery("#loader").show()});jQuery(".ajaxDeleteImage").click(function(e){e.preventDefault();var c=jQuery(this);var b=jQuery(this).attr("href");var d=jQuery("#csrf").val();jQuery("#dialog p").text("Opravdu chcete pokračovat v mazání?");jQuery("#dialog").dialog({resizable:false,width:350,height:200,modal:true,buttons:{Smazat:function(){jQuery("#loader, .loader").show();jQuery.post(b,{csrf:d},function(f){if(f=="success"){jQuery("#loader, .loader").hide();c.children("img").hide("explode",500)}else{alert(f)}});jQuery(this).dialog("close")},"Zrušit":function(){jQuery(this).dialog("close")}}});return false});jQuery(".ajaxDelete").click(function(d){d.preventDefault();var e=jQuery(this).parents("article");var b=jQuery(this).attr("href");var c=jQuery("#csrf").val();jQuery("#dialog p").text("Opravdu chcete pokračovat v mazání?");jQuery("#dialog").dialog({resizable:false,width:350,height:200,modal:true,buttons:{Smazat:function(){jQuery.post(b,{csrf:c},function(f){if(f=="success"){e.fadeOut()}else{alert(f)}});jQuery(this).dialog("close")},"Zrušit":function(){jQuery(this).dialog("close")}}});return false});jQuery(".ajaxReload").click(function(){event.preventDefault();var b=jQuery(this).attr("href");var c=jQuery("#csrf").val();jQuery("#dialog p").text("Opravdu chcete pokračovat?");jQuery("#dialog").dialog({resizable:false,width:350,height:200,modal:true,buttons:{Ano:function(){jQuery.post(b,{csrf:c},function(d){if(d=="success"){location.reload()}else{alert(d)}})},Ne:function(){jQuery(this).dialog("close")}}});return false});jQuery(".ajaxChangestate").click(function(){var b=jQuery(this).attr("href");var c=jQuery("#csrf").val();jQuery.post(b,{csrf:c},function(d){if(d=="active"||d=="inactive"){location.reload()}else{alert(d)}});return false});jQuery("#hledat").click(function(b){b.preventDefault();jQuery(".search").submit()});jQuery("#hledatHastrman").click(function(b){b.preventDefault();jQuery(".fulltextsearch").submit()})});