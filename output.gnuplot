set grid y
set xtics rotate 45
set datafile separator "|"
set style data histograms
set ytics 10
set ylabel "Times"
set border linewidth 2
set terminal pngcairo size 800,600 enhanced font "DejaVu Sans,16"

set bmargin 15
set output 'class-times.png'
plot "< sqlite3 pole.db 'select class,count(*) as c from history group by class order by class'"using 2:xtic(1) title "Class"

set bmargin 6
set output 'class-who.png'
plot "< sqlite3 pole.db 'select who,count(who) as c from history group by who order by who'"using 2:xtic(1) title "Who"
