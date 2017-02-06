<!DOCTYPE html>
<html lang="fa">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>@yield('title')</title>
	<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
	@include('iranpayment.styles.pure')
	@include('iranpayment.styles.style')
</head>
<body>
	@yield('content')
	@yield('javascript')
	@include('iranpayment.sections.footer')
</body>
</html>