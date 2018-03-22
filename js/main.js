function shipInfo(shipid) {
    var xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == XMLHttpRequest.DONE) {   // XMLHttpRequest.DONE == 4
            if (xmlhttp.status == 200) {
                //document.getElementById("shipInfo").innerHTML = xmlhttp.responseText;
                //console.log(xmlhttp.response);
                var data = JSON.parse(xmlhttp.response);

                if (data.shipinfo.country=="russia"){shipinfo_block.style.cssFloat = "left";}
                if (data.shipinfo.country=="japan"){shipinfo_block.style.cssFloat = "right";}

                ship_name.innerHTML = data.shipinfo.name;
                ship_speed.innerHTML = 'Скорость:&nbsp;'+data.shipinfo.speed+"&nbsp;уз."+"&nbsp;("+"фактическая:&nbsp;"+data.shipinfo.fact_speed+"&nbsp;уз."+")";
                ship_crew.innerHTML = 'Экипаж:&nbsp;'+data.shipinfo.crew+'%';
                ship_belt.innerHTML = 'Главный пояс:&nbsp;'+data.shipinfo.belt;
                ship_armour_type.innerHTML = 'Тип бронирования:&nbsp;'+data.shipinfo.armour_type;

                ship_fires.innerHTML = 'Пожары:&nbsp;'+data.shipinfo.fires+'%';
                ship_flooding.innerHTML = 'Затопления:&nbsp;'+data.shipinfo.flooding+'%';

                var cannons_arr = data.cannons;

                cannons.innerHTML = "<p>Бортовой залп:</p>";
                cannons_arr.forEach(function(item, i, cannons_arr) {
                    var newP = document.createElement('p');
                    newP.innerHTML = item.caliber+'"'+'/'+item.barrel_length + '-'+item.quantity+"&nbsp;("+"в строю:&nbsp;"+item.active_quantity+")";
                    cannons.appendChild(newP);
                });


            }
            else if (xmlhttp.status == 400) {
                alert('There was an error 400');
            }
            else {
                alert('something else other than 200 was returned');
            }
        }
    };

    xmlhttp.open("GET", "shipinfo.php?shiprequest=shipinfo&shipid="+shipid, true);
    xmlhttp.send();
}

function shipInit() {
    var xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == XMLHttpRequest.DONE) {   // XMLHttpRequest.DONE == 4
            if (xmlhttp.status == 200) {
                var data = JSON.parse(xmlhttp.response);
                console.log(data);
                var rus_ships = data[0].rus_ships;
                var jap_ships = data[0].jap_ships;
                rus_ships.forEach(function(item, i, rus_ships) {
                    var newRusLi = document.createElement('li');
                    newRusLi.innerHTML = "<a href='javascript:void(0);' onclick='shipInfo("+item.id+");'><p class='ship_name'>"+item.name+"</p></a><img src='/images/'"+item.image+" />"
                        +item.fires_line+item.flooding_line+item.crew_line;
                    rus_ul.appendChild(newRusLi);
                });

                jap_ships.forEach(function(item, i, jap_ships) {
                    var newJapLi = document.createElement('li');
                    newJapLi.innerHTML = "<a href='javascript:void(0);' onclick='shipInfo("+item.id+");'><p class='ship_name'>"+item.name+"</p></a><img src='/images/'"+item.image+" />"
                        +item.fires_line+item.flooding_line+item.crew_line;
                    jap_ul.appendChild(newJapLi);
                });

            }
            else if (xmlhttp.status == 400) {
                alert('There was an error 400');
            }
            else {
                alert('something else other than 200 was returned');
            }
        }
    };

    xmlhttp.open("GET", "shipinfo.php?shiprequest=shipinit", true);
    xmlhttp.send();
}

shipInit();
