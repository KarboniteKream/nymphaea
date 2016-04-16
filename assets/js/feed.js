$(document).ready(function() {
	$("#overlay").click(function() {
		$("#overlay").fadeOut("fast");
		$(".popup").fadeOut("fast");
	});

	$("#overlay").hide();
	$("#add-subscription").hide();
	$(".popup").hide();

	$("article a").attr("target", "_blank");

	$(".sortable").sortable({
		connectWith: ".connected"
	});

	$("input").on("input", function() {
		if($(this).val() != "") {
			$(this).removeClass("input-empty");
		}
	});

	$(".action-bar").on("click", ".remove-article", function() {
		$(this).parent().parent().slideUp(function() {
			$(this).remove();
		});
	});

	$("input[type='submit']").click(function()
	{
		$(this).prevAll("input").filter(function() {
			return !this.value;
		}).addClass("input-empty");

		$(this).prevAll("input").filter(function() {
			return this.value;
		}).removeClass("input-empty");

		if($(this).prevAll().hasClass("input-error") || $(this).prevAll().hasClass("input-empty")) {
			event.preventDefault();
		}
	});

	$("#new-subscription").click(function() {
		$("#add-subscription").fadeIn("fast");
	});

	$("#add-subscription input[type='submit']").click(function() {
	    $("#add-subscription").fadeOut("fast");
	});

	$(".action-bar a:contains('ike')").click(function() {
		$.ajax({
			url: "/like",
			type: "POST",
			data: {
				"do": $(this).text(),
				"id": $(this).parent().parent().attr("id")
			}
		});

	    $(this).parent().parent().toggleClass("liked");
		$(this).text() == "Like" ? $(this).text("Unlike") : $(this).text("Like");
		$(this).parent().parent().removeClass("unread");
		$(this).next().text("Mark as unread");
	});

	$(".action-bar a:contains('read')").click(function() {
		$.ajax({
			url: "/read",
			type: "POST",
			data: {
				"do": $(this).text(),
				"id": $(this).parent().parent().attr("id")
			}
		});

		$(this).parent().parent().toggleClass("unread");
		$(this).text() == "Mark as read" ? $(this).text("Mark as unread") : $(this).text("Mark as read");
	});

	$(".open-popup").click(function() {
		$("#overlay").fadeIn("fast");
		$($(this).attr("target-popup")).fadeIn("fast");
	});

	$("input[name$='email']").blur(function() {
		if(validateEmail($(this).val()) == false) {
			$(this).addClass("input-error");
		}
	});

	$("input[name$='email']").on("input", function() {
		if(validateEmail($(this).val()) == true) {
			$(this).removeClass("input-error");
		}
	});

	$("span:contains('Refresh')").click(function() {
		location.reload();
	});

	$(".confirm-password, .confirm-email").blur(function() {
		if($(this).prev().prev().val() != $(this).val()) {
			$(this).prev().prev().addClass("input-error");
			$(this).addClass("input-error");
		} else {
			$(this).prev().prev().removeClass("input-error");
			$(this).removeClass("input-error");
		}
	});

	$(".confirm-password, .confirm-email").on("input", function() {
		if($(this).prev().prev().val() == $(this).val()) {
			$(this).prev().prev().removeClass("input-error");
			$(this).removeClass("input-error");
		}
	});

	$("#fullscreen").click(function() {
		$("#landing").toggleClass("fullscreen-absolute");
		($(this).text() == "v") ? $(this).text("u") : $(this).text("v");
	});
});

function validateEmail(email) {
    var regex = /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/;
	return (regex.test(email) == true);
}
