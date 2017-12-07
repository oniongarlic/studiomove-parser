set grid y
set xtics rotate 45
set bmargin 18
set datafile separator "|"
set style data histograms
set ytics 5 nomirror
set ylabel "Times"
set terminal png size 800,800 enhanced font "Helvetica,12"
set output 'class-times.png'
plot "< sqlite3 pole.db 'select class,count(*) as c from history group by class order by class'"using 2:xtic(1) title "Class"
set output 'class-who.png'
plot "< sqlite3 pole.db 'select who,count(who) as c from history group by who order by who'"using 2:xtic(1) title "Who"
