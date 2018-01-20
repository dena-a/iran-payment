<style type="text/css">
* {
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
}

@font-face {
	font-family: "IranSans";
	font-style: normal;
	font-weight: bold;
	src: url("https://raw.githubusercontent.com/dena-a/iran-payment/master/src/resources/assets/fonts/IRANSansWeb_Bold.woff2") format("woff2");
}

@font-face {
	font-family: "IranSans";
	font-weight: 300;
	font-style: normal;
	src: url("https://raw.githubusercontent.com/dena-a/iran-payment/master/src/resources/assets/fonts/IRANSansWeb_Light.woff2") format("woff2");
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
	background: #f5f5f5;
	background: radial-gradient(#f5f5f5, #e0e0e0);
	background: -webkit-radial-gradient(#f5f5f5, #e0e0e0);
	background-attachment: fixed !important;
}

.container {
	width: 100%;
	height: 100%;
	margin-top: 10px;
}

.content {
	padding: 1em 1em 3em;
	width: 50%;
	margin: auto;
	padding-top: auto;
	top: 50px;
	text-align: center;
}

.content-head {
	font-weight: 400;
	color: #455a64;
	border: 3px solid #455a64;
	padding: 1em 1.6em;
	margin: 1em 0 1em 0;
	border-radius: 5px;
	letter-spacing: 0.1em;
	line-height: 1.7em;
}

.pure-button {
	padding: 0.5em 2em;
	margin: 0.5em 2em 0.8em 2em;
	border-radius: 5px;
}

button.pure-button-default {
	color: white;
	background: #455a64;
	font-size: 150%;
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
		width: 85%;
	}

	.content-head {
		font-size: 150%;
	}

}
</style>