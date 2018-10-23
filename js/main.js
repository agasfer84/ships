"use strict";

var url = "action.php";
var id = 1;
var target_list = [];

function promiseRequest(data) {
    console.log(data);

    return data;
}


function shipInfo(shipid) {

    var action = "shipInfo";
    var params = JSON.stringify({});

    get(url, action, shipid, params).then(promiseRequest).then(
        function(data){

            if (data.shipinfo.country=="russia"){shipinfo_block.style.cssFloat = "left";}
            if (data.shipinfo.country=="japan"){shipinfo_block.style.cssFloat = "right";}


            ship_name.innerHTML = data.shipinfo.name;
            ship_speed.innerHTML = 'Скорость:&nbsp;'+data.shipinfo.speed+"&nbsp;уз."+"&nbsp;("+"фактическая:&nbsp;"+data.shipinfo.fact_speed+"&nbsp;уз."+")";
            ship_crew.innerHTML = 'Экипаж:&nbsp;'+data.shipinfo.crew+'%';
            ship_belt.innerHTML = 'Главный пояс:&nbsp;'+data.shipinfo.belt;
            ship_armour_type.innerHTML = 'Тип бронирования:&nbsp;'+data.shipinfo.armour_type;
            ship_armour_effective.innerHTML = 'Эффективная толщина брони:&nbsp;'+data.shipinfo.effective_armour;

            ship_fires.innerHTML = 'Пожары:&nbsp;'+data.shipinfo.fires+'%';
            ship_flooding.innerHTML = 'Затопления:&nbsp;'+data.shipinfo.flooding+'%';

            var cannons_arr = data.cannons;

            cannons.innerHTML = "<p>Бортовой залп:</p>";
            cannons_arr.forEach(function(item, i, cannons_arr) {
                var newP = document.createElement('p');
                newP.innerHTML = item.caliber+'"'+'/'+item.barrel_length + '-'+item.quantity+"&nbsp;("+"в строю:&nbsp;"+item.active_quantity+")";
                cannons.appendChild(newP);
            });

        });
}

function shipInit() {

    var action = "shipInit";
    var params = JSON.stringify({});

    target_list =[];
    buttonEnabled();

    ship_name.innerHTML ="";
    ship_speed.innerHTML="";
    ship_crew.innerHTML ="";
    ship_belt.innerHTML ="";
    ship_armour_type.innerHTML ="";
    ship_armour_effective.innerHTML ="";
    ship_fires.innerHTML ="";
    ship_flooding.innerHTML ="";
    cannons.innerHTML ="";

    get(url, action, id, params).then(promiseRequest).then(
        function(data){
            rus_ul.innerHTML="";
            jap_ul.innerHTML="";
            min_speed_rus.innerHTML="Скорость эскадры:&nbsp;"+data[0].rus_ships_speed+"&nbsp;уз.";
            min_speed_jap.innerHTML="Скорость эскадры:&nbsp;"+data[0].jap_ships_speed+"&nbsp;уз.";

            var rus_ships = data[0].rus_ships;
            var jap_ships = data[0].jap_ships;
            rus_ships.forEach(function(item, i, rus_ships) {
                var newRusLi = document.createElement('li');
                //var newRusSelect = document.createElement('li');
                newRusLi.innerHTML = "<a href='javascript:void(0);' onclick='shipInfo("+item.id+");'><p class='ship_name'>"+item.name+"</p></a><img src='/images/'"+item.image+" />"
                    +item.fires_line+item.flooding_line+item.crew_line+item.enemy_list+item.exit_button;
                rus_ul.appendChild(newRusLi);
            });

            jap_ships.forEach(function(item, i, jap_ships) {
                var newJapLi = document.createElement('li');
                newJapLi.innerHTML = "<a href='javascript:void(0);' onclick='shipInfo("+item.id+");'><p class='ship_name'>"+item.name+"</p></a><img src='/images/'"+item.image+" />"
                    +item.fires_line+item.flooding_line+item.crew_line;
                jap_ul.appendChild(newJapLi);
            });

        }
    );
}

function setTarget(enemy_id, ship_id)
{
    //console.log(enemy_id, ship_id);
    if (!enemy_id) {
        return alert("Выберите цель!");
    }

    target_list[ship_id] = {ship_id : ship_id, enemy_id : enemy_id};
}

function fire()
{
    console.log(target_list);

    var action = "fire";
    var body = JSON.stringify({"target_list" : target_list});

    post(url, action, body).then(promiseRequest).then(
        function(data){
            log_fraim.innerHTML = "";

            data.forEach(function(item, i, data) {
                var newP = document.createElement('p');
                newP.innerHTML = item.name+"&nbsp;стреляет по&nbsp;"+item.enemy_name+"&nbsp;орудие&nbsp;"+item.caliber+'"/'+item.barrel_length + '&nbsp;Результат:&nbsp;'+item.fire_result_name+"-"+item.fire_result_type_name;
                log_fraim.appendChild(newP);
            });

            shipInit();
        });
}

function exitShip(shipid)
{
    var action = "exitShip";
    var params = JSON.stringify({});

    get(url, action, shipid, params).then(promiseRequest);
}

function  buttonEnabled() {
    console.log(target_list);
    if(target_list.length>0){
        fire_button.removeAttribute("disabled");
    }
    else{
        fire_button.setAttribute("disabled", "disabled");
    }
}


function shipList() {
    var action = "shipList";
    var params = JSON.stringify({});

    get(url, action, id, params).then(promiseRequest).then(
        function(data){
            console.log(data);
        }
    );
}

window.onload = function() {
    shipInit();
    //shipList();
};
