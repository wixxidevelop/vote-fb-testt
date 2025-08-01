

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Oops!</title>
    <style>
      * {
        transition: all 0.6s;
      }

      html {
        height: 100%;
      }

      body {
        font-family: "Lato", sans-serif;
        color: #888;
        margin: 0;
      }

      #main {
        display: table;
        width: 100%;
        height: 100vh;
        text-align: center;
      }

      .fof {
        display: table-cell;
        vertical-align: middle;
      }

      .fof h1 {
        font-size: 50px;
        display: inline-block;
        padding-right: 12px;
        animation: type 0.5s alternate infinite;
      }

      @keyframes type {
        from {
          box-shadow: inset -3px 0px 0px #888;
        }
        to {
          box-shadow: inset -3px 0px 0px transparent;
        }
      }
    </style>
  </head>
  <body>
    <div id="main">
      <div class="fof">
        <h1>Ooops, The Event or page has <span style="color:red;">expired</span></h1>
      </div>
    </div>

    <script></script>
  </body>
</html>
