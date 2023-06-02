@if (file_exists(public_path('assets/vendor/iranpayment/fonts/IRANSansWeb_Bold.woff2')))
	<style type="text/css">
	@font-face {
		font-family: "IranSans";
		font-style: normal;
		font-weight: bold;
		src: url("assets/vendor/iranpayment/fonts/IRANSansWeb_Bold.woff2") format("woff2");
	}

	@font-face {
		font-family: "IranSans";
		font-weight: 300;
		font-style: normal;
		src: url("assets/vendor/iranpayment/fonts/IRANSansWeb_Light.woff2") format("woff2");
	}
	</style>
@else
	<style type="text/css">
	@font-face {
		font-family: "IranSans";
		font-style: normal;
		font-weight: bold;
		src: url("https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/fonts/IRANSansWeb_Bold.woff2") format("woff2");
	}

	@font-face {
		font-family: "IranSans";
		font-weight: 300;
		font-style: normal;
		src: url("https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/fonts/IRANSansWeb_Light.woff2") format("woff2");
	}
	</style>
@endif

<style type="text/css">
* {
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
}

h1,
h2,
h3,
h4,
h5,
h6,
label,
a,
p,
button,
body {
	font-family: 'IranSans';
	font-weight: normal;
	font-style: normal;
	color: #455a64;
}

.pure-img-responsive {
	max-width: 100%;
	height: auto;
}

body {
	line-height: 1.7em;
	font-size: 13px;
	direction: rtl;
}

.container {
	background: #f5f5f5;
	background: radial-gradient(#f5f5f5, #e0e0e0);
	background: -webkit-radial-gradient(#f5f5f5, #e0e0e0);
	background-attachment: fixed !important;
	width: 100%;
	height: 100%;
	top: 0;
	left: 0;
	right: 0;
	position: fixed !important;
	overflow-y: scroll;
}

.content {
	padding: 1em 1em 3em;
	width: 50%;
	margin: auto;
	padding-top: auto;
	margin-top: 50px;
	text-align: center;
}

.content-head {
	font-weight: 400;
	color: #455a64;
	border: 3px solid #455a64;
	padding: 1em 1.6em;
	border-radius: 5px;
	letter-spacing: 0.1em;
	line-height: 1.7em;
}

.pure-button {
	padding: 0.5em 2em;
	border-radius: 5px;
}

button.pure-button-default,
a.pure-button-default {
	color: white;
	background: #455a64;
	font-size: 150%;
}

.button-success,
.button-error,
.button-warning,
.button-secondary {
    color: white;
    border-radius: 4px;
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
}

.button-success {
    background: rgb(28, 184, 65);
}

.button-error {
    background: rgb(202, 60, 60);
}

footer {
	background-color: #263238;
	color: #fafafa;
	position: fixed;
	bottom: 0;
	width: 100%;
}

footer a {
	color: #fafafa;
	text-decoration: none;
}

.text-ltr {
	direction: ltr;
}

.loading-bar {
	background: #607d8b;
	width: 0%;
	height: 10px;
	z-index: 1;
	box-shadow: -8px 0px 0px #9e9e9e;
	position: fixed;
	top: 0px;
	right: 0px;
	left: 0px;
}

.l-box {
	padding: 1em;
}

.l-box-lrg {
	padding: 2em;
}

.is-center {
	text-align: center;
}

@media (min-width: 48em) {

	body {
		font-size: 13px;
	}

	.l-box-lrg {
		border: none;
	}

}

@media (max-width: 48em) {

	.content {
		width: 95%;
		margin-top: 0px;
	}

	.content-head {
		font-size: 150%;
		padding: 1em 0;
		line-height: 1.2em;
	}

}
</style>
