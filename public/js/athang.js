/*!
* Athang v1.0.0 (https://athang.com)
* @version 1.0.0
* @link https://athang.com
* Copyright 2021-2022 The Athang IC Tech
* Copyright 2021-2022 chophel@athang.com
*/
$(document).ready(function(){
	/* ---------------------------------------------------------------------- */
	/*  Modal -- Content loaded via jQuery's load method
	/* ---------------------------------------------------------------------- */
	$('body').find('.modal').on('show.bs.modal', function (e) {
		$(this).find('.modal-content').load(e.relatedTarget.href);
	});
	$('body').on('hidden.bs.modal', '.modal', function (e) {
		$(this).find('.modal-content').empty();
	}); 
	/* ---------------------------------------------------------------------- */
	/*  Tooltip -- Initialize tooltip
	/* ---------------------------------------------------------------------- */
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
	var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl)
	});
	/* ---------------------------------------------------------------------- */
	/*  Toast -- Initialize toasts
	/* ---------------------------------------------------------------------- */
	var toastElList = [].slice.call(document.querySelectorAll('.toast'))
	var toastList = toastElList.map(function (toastEl) {
		return new bootstrap.Toast(toastEl)
	});
	toastList.forEach(toast => toast.show()); /*This show them
	console.log(toastList);*/
	/* ---------------------------------------------------------------------- */
	/*  Button -- Back to top button
	/* ---------------------------------------------------------------------- */
	/* Get the button*/
	let mybutton = document.getElementById("btn-back-to-top");

	/* When the user scrolls down 20px from the top of the document, show the button*/
	window.onscroll = function () {
		scrollFunction();
	};

	function scrollFunction() {
		if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
			mybutton.style.display = "block";
		} else {
			mybutton.style.display = "none";
		}
	}
	/* When the user clicks on the button, scroll to the top of the document*/
	mybutton.addEventListener("click", backToTop);

	function backToTop() {
		document.body.scrollTop = 0;
		document.documentElement.scrollTop = 0;
	}
	/* ---------------------------------------------------------------------- */
	/*  Chosen.jquery -- Bootstrap4C
	/* ---------------------------------------------------------------------- */
	$('form select').chosen();
	/* ---------------------------------------------------------------------- */
	/*  CanvasJS -- ColorSet
	/* ---------------------------------------------------------------------- */
	CanvasJS.addColorSet('skyColorSet',['#bccad6','#8d9db6','#667292','#f1e3dd','#cfe0e8','#b7d7e8','#87bdd8','#daebe8']);
	CanvasJS.addColorSet('sandColorSet',['#e0876a','#d9ad7c','#a2836e','#674d3c','#fbefcc','#f9ccac','#f4a688','#fff2df']);
	CanvasJS.addColorSet('rusticColorSet',['#8ca3a3','#c6bcb6','#96897f','#625750','#c8c3cc','#563f46'],'#484f4f','#e0e2e4');
	CanvasJS.addColorSet('beachColorSet',['#96ceb4','#ffeead','#ffcc5c','#ff6f69','#588c7e','#f2e394','#f2ae72','#d96459']);
   
});
	