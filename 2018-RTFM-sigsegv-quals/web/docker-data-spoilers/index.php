<?php
if ($_SERVER['REQUEST_URI'] != '/' && !strstr($_SERVER['REQUEST_URI'],'index.php')){
    header('HTTP/1.0 404 Not Found');
?>
<!doctype html><html><head><title>404 Not Found</title><style>
body { background-color: #fcfcfc; color: #333333; margin: 0; padding:0; }
h1 { font-size: 1.5em; font-weight: normal; background-color: #9999cc; min-height:2em; line-height:2em; border-bottom: 1px inset black; margin: 0; }
h1, p { padding-left: 10px; }
code.url { background-color: #eeeeee; font-family:monospace; padding:0 2px;}
</style>
</head><body><h1>Not Found</h1><p>The requested resource <code class="url"><?php echo $_SERVER['REQUEST_URI']; ?></code> was not found on this server.</p></body></html>
<?php
    exit();
}
?>
<html>
    <head>
        <title>Un site simple</title></title>
    </head>
    <body>
        <center><iframe width="560" height="315" src="https://www.youtube.com/embed/2bjk26RwjyU?rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe></center>
<?php
	if(isset($_POST["h1"]))
	{
		$h1 = md5($_POST["h1"] . "Shrewk");
		echo "h1 vaut: ".$h1."</br>";
		if($h1 == "0")
		{
				echo "<!--Bien joué le flag est sigsegv{...}-->";
		}
	}
?>
<!-- Si une méthode ne fonctionne pas il faut en utiliser une autre -->
<!-- Un formulaire c'était pas assez simple donc on en a pas mis -->
</body>
</html>
