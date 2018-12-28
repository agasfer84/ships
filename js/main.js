"use strict";

var url = "action.php";
var id = 1;
var target_list = [];
var shipsToForce = [];
var forcesToRegion = [];

function promiseRequest(data) {
    console.log(data);

    return data;
}

/*battle interface*/
function shipInfo(shipid) {

    var action = "shipInfo";
    var params = JSON.stringify({});

    get(url, action, shipid, params).then(promiseRequest).then(
        function(data) {

            if (data.shipinfo.country == "russia") document.getElementById("shipinfo_block").style.cssFloat = "left";

            if (data.shipinfo.country == "japan") document.getElementById("shipinfo_block").style.cssFloat = "right";

            document.getElementById("ship_name").innerHTML = data.shipinfo.name;
            document.getElementById("ship_speed").innerHTML = 'Скорость:&nbsp;' + data.shipinfo.speed + "&nbsp;уз." + "&nbsp;(" + "фактическая:&nbsp;" + data.shipinfo.fact_speed + "&nbsp;уз." + ")";
            document.getElementById("ship_crew").innerHTML = 'Экипаж:&nbsp;' + data.shipinfo.crew + '%';
            document.getElementById("ship_belt").innerHTML = 'Главный пояс:&nbsp;' + data.shipinfo.belt;
            document.getElementById("ship_armour_type").innerHTML = 'Тип бронирования:&nbsp;' + data.shipinfo.armour_type;
            document.getElementById("ship_armour_effective").innerHTML = 'Эффективная толщина брони:&nbsp;' + data.shipinfo.effective_armour;
            document.getElementById("ship_fires").innerHTML = 'Пожары:&nbsp;' + data.shipinfo.fires + '%';
            document.getElementById("ship_flooding").innerHTML = 'Затопления:&nbsp;' + data.shipinfo.flooding + '%';

            var cannons_arr = data.cannons;
            document.getElementById("cannons").innerHTML = "<p>Бортовой залп:</p>";

            cannons_arr.forEach(function(item, i, cannons_arr) {
                var newP = document.createElement('p');
                newP.innerHTML = item.caliber + '"' + '/'+item.barrel_length + '-' + item.quantity + "&nbsp;(" + "в строю:&nbsp;" + item.active_quantity + ")";
                document.getElementById("cannons").appendChild(newP);
            });
        });
}

function shipInit() {

    if (!document.getElementById("battle_frame")) return false;

    var action = "shipInit";
    var params = JSON.stringify({});

    target_list = [];
    buttonEnabled();

    document.getElementById("ship_name").innerHTML = "";
    document.getElementById("ship_speed").innerHTML = "";
    document.getElementById("ship_crew").innerHTML = "";
    document.getElementById("ship_belt").innerHTML = "";
    document.getElementById("ship_armour_type").innerHTML = "";
    document.getElementById("ship_armour_effective").innerHTML = "";
    document.getElementById("ship_fires").innerHTML = "";
    document.getElementById("ship_flooding").innerHTML = "";
    document.getElementById("cannons").innerHTML = "";

    get(url, action, id, params).then(promiseRequest).then(
        function(data) {
            document.getElementById("rus_ul").innerHTML = "";
            document.getElementById("jap_ul").innerHTML = "";
            document.getElementById("min_speed_rus").innerHTML = "Скорость эскадры:&nbsp;" + data[0].rus_ships_speed + "&nbsp;уз.";
            document.getElementById("min_speed_jap").innerHTML = "Скорость эскадры:&nbsp;" + data[0].jap_ships_speed + "&nbsp;уз.";

            var rus_ships = data[0].rus_ships;
            var jap_ships = data[0].jap_ships;

            rus_ships.forEach(function(item, i, rus_ships) {
                var newRusLi = document.createElement('li');
                //var newRusSelect = document.createElement('li');
                newRusLi.innerHTML = "<a href='javascript:void(0);' onclick='shipInfo(" + item.id + ");'><p class='ship_name'>" + item.name + "</p></a><img src='/images/'" + item.image + " />"
                    + item.fires_line + item.flooding_line + item.crew_line + item.enemy_list + item.exit_button;
                document.getElementById("rus_ul").appendChild(newRusLi);
            });

            jap_ships.forEach(function(item, i, jap_ships) {
                var newJapLi = document.createElement('li');
                newJapLi.innerHTML = "<a href='javascript:void(0);' onclick='shipInfo(" + item.id + ");'><p class='ship_name'>" + item.name + "</p></a><img src='/images/'" + item.image + " />"
                    + item.fires_line + item.flooding_line + item.crew_line;
                document.getElementById("rus_ul").appendChild(newJapLi);
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
    //console.log(target_list);

    var action = "fire";
    var body = JSON.stringify({"target_list" : target_list});

    post(url, action, body).then(promiseRequest).then(
        function(data){
            var log_frame = document.getElementById("log_frame");
            log_frame.innerHTML = "";

            data.forEach(function(item, i, data) {
                var newP = document.createElement('p');
                newP.innerHTML = item.name + "&nbsp;стреляет по&nbsp;" + item.enemy_name + "&nbsp;орудие&nbsp;" + item.caliber + '"/' + item.barrel_length + '&nbsp;Результат:&nbsp;' + item.fire_result_name + "-" + item.fire_result_type_name;
                log_frame.appendChild(newP);
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
    //console.log(target_list);
    var fire_button = document.getElementById("fire_button");

    if (!fire_button) return false;

    if (target_list.length > 0) {
        fire_button.removeAttribute("disabled");
    } else {
        fire_button.setAttribute("disabled", "disabled");
    }
}

/*end battle interface*/

 /*forces interface*/

function forcesList() {
    var action = "forcesList";
    var params = JSON.stringify({});
    var list_frame = document.getElementById("list_frame");
    var forces_frame = document.getElementById("forces_frame");

    get(url, action, id, params).then(promiseRequest).then(
        function(data){
            forces_frame.innerHTML = "";
            var newUl = document.createElement('ul');
            document.getElementById("forces_frame").appendChild(newUl);

            var forcesSelect = document.createElement('select');
            forcesSelect.setAttribute("id", "forcesSelect");
            list_frame.appendChild(forcesSelect);

            var changeForceButton = document.createElement('button');
            changeForceButton.setAttribute("onclick", "changeForce()");
            changeForceButton.innerHTML = "Назначить отряд";
            list_frame.appendChild(changeForceButton);


            data.forEach(function(force) {
                var newLi = document.createElement('li');
                newLi.innerHTML = force.force_name + '<input type="checkbox" name="forcesInRegionCheckboxes" onchange="forcesInRegion(this)" value=' + force.id + '>';
                newUl.appendChild(newLi);

                var newOption = document.createElement('option');
                newOption.innerHTML = force.force_name;
                newOption.value = force.id;
                forcesSelect.appendChild(newOption);
            });

            var newForceInput = document.createElement('input');
            newForceInput.setAttribute("style", "width: 400px;");
            newForceInput.setAttribute("id", "newForceName");
            forces_frame.appendChild(newForceInput);

            var newForceButton = document.createElement('button');
            newForceButton.innerHTML = "Создать отряд";
            newForceButton.setAttribute("onclick", "createNewForce();");
            forces_frame.appendChild(newForceButton);
        });
}


function shipList() {

    if (!document.getElementById("list_frame")) return false;

    var action = "shipList";
    var params = JSON.stringify({});

    get(url, action, id, params).then(promiseRequest).then(
        function(data){
            //console.log(data);
            document.getElementById("list_frame").innerHTML = "";

            var newUl = document.createElement('ul');
            document.getElementById("list_frame").appendChild(newUl);

            data.forEach(function(force) {
                var newLi = document.createElement('li');
                newLi.innerHTML = force.force_name;
                newUl.appendChild(newLi);
                var newNestedUl = document.createElement('ul');
                newLi.appendChild(newNestedUl);

                var force_ships = force.force_ships;

                force_ships.forEach(function(ship) {
                    var newNestedLi = document.createElement('li');
                    newNestedLi.innerHTML = '<a href="javascript:void(0);" onclick="shipInfo(' + ship.id + ');">' + ship.name + '</a>' + '<input type="checkbox" name="shipsInForcesCheckboxes" onchange="shipsInForces(this)" value=' + ship.id + '>';
                    newNestedUl.appendChild(newNestedLi);
                    }
                );
            });

            forcesList();
        }
    );
}

function createNewForce() {
    var newForceNameInput = document.getElementById("newForceName");

    if (!newForceNameInput.value) return false;

    //console.log(newForceNameInput.value);
    var action = "createNewForce";
    var body = JSON.stringify({"force_name" : newForceNameInput.value});

    post(url, action, body).then(promiseRequest).then( function () {
        forcesList();
    });
}

function forcesInRegion(checkbox) {
    if (checkbox.checked == true) {
        forcesToRegion.push(checkbox.value);
    } else {
        var index = forcesToRegion.indexOf(checkbox.value);
        forcesToRegion.shift(index);
    }
}

function shipsInForces(checkbox) {
    if (checkbox.checked == true) {
        shipsToForce.push(checkbox.value);
    } else {
        var index = shipsToForce.indexOf(checkbox.value);
        shipsToForce.shift(index);
    }
}

function changeForce() {
    var forcesSelect = document.getElementById("forcesSelect");
    var action = "shipsToForce";
    var body = JSON.stringify({"forceId" : forcesSelect.value, "shipsToForce" : shipsToForce});

    if (shipsToForce.length < 1) return false;

    post(url, action, body).then(promiseRequest).then( function () {
            shipList();
    });

}

/* end forces interface*/

window.onload = function() {
    shipInit();
    shipList();
};
