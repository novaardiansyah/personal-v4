<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #ffffff;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 600px;
      margin: auto;
      padding: 40px 20px;
      border: 1px solid #e0e0e0;
      background-color: #ffffff;
    }
    .text-primary {
      color: #3366FF;
    }
    .header {
      text-align: center;
      color: #3366FF;
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 20px;
    }
    .content {
      font-size: 16px;
      color: #333333;
      line-height: 1.6;
    }

    .card {
      background-color: #f7f8fa;
      border-radius: 8px;
      padding: 15px;
      color: #232323;
      box-shadow: 0 1px 4px rgba(60, 60, 60, 0.06);
      font-size: 14px;
    }
    .card h4, .card h5 {
      margin-top: 0;
      color: #3366FF;
    }

    .list-flush {
      list-style-type: none;
      margin: 0;
      padding: 0;
      border-radius: 8px;
      overflow: hidden;
    }
    .list-flush li {
      background-color: #ecedf1;
      padding: 10px 15px;
      border-bottom: 1px solid rgba(60, 60, 60, 0.08);
    }
    .list-flush li:first-child {
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
    }
    .list-flush li:last-child {
      border-bottom: none;
      border-bottom-left-radius: 8px;
      border-bottom-right-radius: 8px;
    }

    a {
      color: #3366FF;
      text-decoration: none;
    }
    
    a:hover {
      opacity: .9;
    }

    .footer, .footer a {
      text-align: center;
      font-size: 13px;
      color: #999;
      margin-top: 40px;
      text-decoration: none;
    }

    .footer a:hover {
      color: #3366FF;
    }

    .mb-2 {
      margin-bottom: 18px;
    }
  </style>
  
  <title>@yield('title')</title>
</head>
<body>
  <div class="container">
    <div class="header">
      @yield('header')
    </div>
    <div class="content">
      @yield('content')

      <p style="margin-bottom: 26px">Terima kasih atas perhatian Anda.</p>

      <p>Salam Hangat,</p>
      <p>{{ explode(' ', config('app.author_name'))[0] }}</p>
    </div>

    <div class="footer">
      &copy; {{ date('Y') }} <a href="https://novaardiansyah.my.id">{{ config('app.author_name') }}</a>. All rights reserved.
    </div>
  </div>
</body>
</html>
