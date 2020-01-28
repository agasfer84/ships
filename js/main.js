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
            document.getElementById("ship_strength").innerHTML = 'Боевая ценность (факт/номинал):&nbsp;' + data.strength_fact + " / " + data.strength_nominal;
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
            if (!data || data.length < 1) {
               checkSwitch();
            }

            document.getElementById("player_ul").innerHTML = "";
            document.getElementById("enemy_ul").innerHTML = "";
            document.getElementById("strength_player").innerHTML = "Сила отряда (факт/номинал):&nbsp;" + data["player_strength_fact"] + "&nbsp;/&nbsp;" + data["player_strength_nominal"];
            document.getElementById("strength_enemy").innerHTML = "Сила отряда (факт/номинал):&nbsp;" + data["enemy_strength_fact"] + "&nbsp;/&nbsp;" + data["enemy_strength_nominal"];
            document.getElementById("min_speed_player").innerHTML = "Скорость эскадры:&nbsp;" + data["player_ships_speed"] + "&nbsp;уз.";
            document.getElementById("min_speed_enemy").innerHTML = "Скорость эскадры:&nbsp;" + data["enemy_ships_speed"] + "&nbsp;уз.";

            var playerShips = data["player_ships"];
            var enemyShips = data["enemy_ships"];
            var enemyListArray = data["enemy_list"];
            var messages = data["messages"];

            messages.forEach(function(item) {
                var newP = document.createElement('p');
                newP.innerHTML = item;
                document.getElementById("log_frame").appendChild(newP);
            });

            playerShips.forEach(function(item) {
                var newPlayerLi = document.createElement('li');
                var exitButton = (item.exit_button) ? '<button onclick="exitShip(' + item.id + ')">Выход из боя</button>' : '<button disabled="disabled">Выход из боя</button>';
                var enemyListSelect = enemyList(item.id, enemyListArray);

                newPlayerLi.innerHTML = "<a href='javascript:void(0);' onclick='shipInfo(" + item.id + ");'><p class='ship_name'>" + item.name + "</p></a><img src='/images/'" + item.image + " />"
                    + drawLine(item.fires, "fires") + drawLine(item.flooding, "flooding") + drawLine(item.crew, "crew") + enemyListSelect + exitButton;
                document.getElementById("player_ul").appendChild(newPlayerLi);
            });

            enemyShips.forEach(function(item) {
                var newEnemyLi = document.createElement('li');
                newEnemyLi.innerHTML = "<a href='javascript:void(0);' onclick='shipInfo(" + item.id + ");'><p class='ship_name'>" + item.name + "</p></a><img src='/images/'" + item.image + " />"
                    + drawLine(item.fires, "fires") + drawLine(item.flooding, "flooding") + drawLine(item.crew, "crew");
                document.getElementById("enemy_ul").appendChild(newEnemyLi);
            });

            populateTargets();
        }
    );
}

function drawLine(value, type) {
    var level = Math.ceil(value * 5 / 100);
    var title = "";
    var lineClass = "";

    if (type == 'fires') {
        title = "Пожары";
        lineClass = "red";
    }

    if (type == 'flooding') {
        title = "Затопления";
        lineClass = "blue";
    }

    if (type == 'crew') {
        title = "Экипаж";
        lineClass = "green";
    }

    var line = '<p class="p_line" title=' + title + '>';

    for (var i = 1; i <= 5; i++) {
        if (i <= level) {
            line += '<span class="oval ' + lineClass + '"></span>';
        } else {
            line += '<span class="oval grey"></span>';
        }
    }

    line += '</p>';

    return line;
}

function enemyList(shipId, ships) {
    var select_open = 'Цель:&nbsp;' + '<select id=' + shipId + ' class="target_list" name="enemy_list" onchange="setTarget(this.options[this.selectedIndex].value,' + shipId + '); buttonEnabled();">';
    var options = '<option value="" selected disabled>Выберите цель...</option>';

    ships.forEach(function(item) {
        options += '<option id=' + shipId + '_' + item.id + ' value=' + item.id + '>' + item.name + '</option>';
    });

    var select_close = '</select>';

    return select_open + options + select_close;
}

function setTarget(enemy_id, ship_id)
{
    //console.log(enemy_id, ship_id);
    if (!enemy_id) {
        return alert("Выберите цель!");
    }

    const target = {ship_id : ship_id, enemy_id : enemy_id};
    var existIndex = target_list.findIndex(function(val){
        return val.ship_id == ship_id;
    });

    if (existIndex === -1) {
        target_list[target_list.length] = target;
    } else {
        target_list[existIndex] = target;
    }

    console.log(target_list);
}

function populateTargets() {
    target_list.forEach(function(item) {
        //console.log(item);
        var option = document.getElementById(item.ship_id + "_" + item.enemy_id);
        //console.log(option);
        if (option) {
            option.setAttribute("selected", "selected");
        }
    });
}

function fire()
{
    //console.log(target_list);
    var action = "fire";
    var body = JSON.stringify(target_list);

    post(url, action, body).then(promiseRequest).then(
        function(data){
            var log_frame = document.getElementById("log_frame");
            log_frame.innerHTML = "";

            data.forEach(function(item, i, data) {
                var newP = document.createElement('p');
                var fireResultClass = "";

                if (item.fire_result && (item.fire_result_side == 'player')) {
                    fireResultClass = "green_text";
                }

                if (item.fire_result && (item.fire_result_side == 'enemy')) {
                    fireResultClass = "red_text";
                }

                var fireResultName = '<span class="' + fireResultClass + '">' + item.fire_result_name + '</span>';
                newP.innerHTML = item.name + "&nbsp;стреляет по&nbsp;" + item.enemy_name + "&nbsp;орудие&nbsp;" + item.caliber + '"/' + item.barrel_length + '&nbsp;Результат:&nbsp;' + fireResultName + "-" + item.fire_result_type_name;
                log_frame.appendChild(newP);
            });

            shipInit();
        });
}

function exitShip(shipid)
{
    var action = "exitShip";
    var params = JSON.stringify({});

    get(url, action, shipid, params).then(promiseRequest).then(function(data) {
        shipInit();
    });
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

    if (!document.getElementById("list_frame")) return false;

    //var forces_frame = document.getElementById("forces_frame");

    get(url, action, id, params).then(promiseRequest).then(
        function (data) {
            var all_forces = data["all"];
            var active_forces = data["active"];

            document.getElementById("forces_frame").innerHTML = "";
            var newUl = document.createElement('ul');
            document.getElementById("forces_frame").appendChild(newUl);

            var forcesSelect = document.getElementById('forcesSelect');

            if (forcesSelect) {
                forcesSelect.innerHTML = "";
            } else {
                forcesSelect = document.createElement('select');
                forcesSelect.setAttribute("id", "forcesSelect");
                list_frame.appendChild(forcesSelect);
            }

            var changeForceButton = document.getElementById('changeForce_button');

            if (!changeForceButton) {
                changeForceButton = document.createElement('button');
                changeForceButton.setAttribute("onclick", "changeForce()");
                changeForceButton.setAttribute("id", "changeForce_button");
                changeForceButton.innerHTML = "Включить в отряд";
                list_frame.appendChild(changeForceButton);
            }

            active_forces.forEach(function(force) {
                var newLi = document.createElement('li');
                newLi.innerHTML = force.force_name + ((force.region_name) ? ' ('+force.region_name+')' : '') + '<input type="checkbox" name="forcesInRegionCheckboxes" onchange="forcesInRegion(this)" value=' + force.id + '>' + '<a href="#" onclick="deleteForce(' + force.id + ')">Удалить</a>';
                newUl.appendChild(newLi);
            });

            all_forces.forEach(function(force) {
                var newOption = document.createElement('option');
                newOption.innerHTML = force.force_name;
                newOption.value = force.id;
                forcesSelect.appendChild(newOption);
            });

            var newForceInput = document.createElement('input');
            newForceInput.setAttribute("style", "width: 400px;");
            newForceInput.setAttribute("id", "newForceName");
            document.getElementById("forces_frame").appendChild(newForceInput);

            var newForceButton = document.createElement('button');
            newForceButton.innerHTML = "Создать отряд";
            newForceButton.setAttribute("onclick", "createNewForce();");
            document.getElementById("forces_frame").appendChild(newForceButton);

            regionsList();
        });
}


function shipList() {
    if (!document.getElementById("list_frame")) return false;

    var action = "shipList";
    var params = JSON.stringify({});

    get(url, action, id, params).then(promiseRequest).then(
        function(data){
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

function regionsList() {
    var action = "regionsList";
    var params = JSON.stringify({});

    var forces_frame = document.getElementById("forces_frame");

    if (!forces_frame) return false;

    get(url, action, id, params).then(promiseRequest).then(
        function(data){
            var regionsSelect = document.getElementById("regionsSelect");

            if (regionsSelect) {
                regionsSelect.innerHTML = "";
            } else {
                regionsSelect = document.createElement('select');
                regionsSelect.setAttribute("id", "regionsSelect");
                forces_frame.appendChild(regionsSelect);
            }

            data.forEach(function(item) {
                var newOption = document.createElement('option');
                newOption.innerHTML = item.region.region_name;
                newOption.value = item.region.id;
                regionsSelect.appendChild(newOption);
            });

            var toRegionButton = document.getElementById('regionsSelect_button');

            if (!toRegionButton) {
                toRegionButton = document.createElement('button');
                toRegionButton.innerHTML = "Отправить в район";
                toRegionButton.setAttribute("id", "regionsSelect_button");
                toRegionButton.setAttribute("onclick", 'sendForcesToRegion();');
                forces_frame.appendChild(toRegionButton);
            }

            populateMap(data);
        });
}

function populateMap(regions)
{
    var map_frame = document.getElementById("map_frame");
    map_frame.innerHTML = "";

    regions.forEach(function(region) {
        var newRegionDiv = document.createElement('div');
        newRegionDiv.setAttribute("class", "map_region");
        newRegionDiv.innerHTML = region.region.region_name;
        map_frame.appendChild(newRegionDiv);

        var forces = region.forces;

        forces.forEach(function(force){
            var newRegionForceP = document.createElement('p');
            newRegionForceP.innerHTML = force.force_name;
            newRegionDiv.appendChild(newRegionForceP);
        });
    });
}

function createNewForce() {
    var newForceNameInput = document.getElementById("newForceName");

    if (!newForceNameInput.value) return false;

    //console.log(newForceNameInput.value);
    var action = "createNewForce";
    var body = JSON.stringify({"force_name" : newForceNameInput.value});

    post(url, action, body).then(promiseRequest).then( function () {
        forcesList();
        shipList();
    });
}

function deleteForce(force_id) {
    if (!force_id) return false;

    var action = "deleteForce";
    var body = JSON.stringify({"id" : force_id});
    var toDel = confirm("Удалить отряд?");

    if (toDel) {
        post(url, action, body).then(promiseRequest).then( function () {
            forcesList();
            shipList();
        });
    }
}

function forcesInRegion(checkbox) {
    if (checkbox.checked == true) {
        forcesToRegion.push(checkbox.value);
    } else {
        var index = forcesToRegion.findIndex( function(element) {
            if (element == checkbox.value) {
                return element;
            }
        });
        forcesToRegion.splice(index, 1);
    }
}

function sendForcesToRegion()
{
    var region_id = document.getElementById("regionsSelect").value;

    if (forcesToRegion.length < 1 || !region_id) return false;

    var action = "sendForcesToRegion";
    var body = JSON.stringify({"forces" : forcesToRegion, "region_id" : region_id});

    //console.log(region_id);

    post(url, action, body).then(promiseRequest).then( function () {
        forcesToRegion = [];
        forcesList();
        regionsList();
    });
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
            shipsToForce = [];
            shipList();
    });
}

function turn() {
    var action = "turn";
    var params = JSON.stringify({});

    get(url, action, id, params).then(promiseRequest).then(
        function (data) {
            checkSwitch();
        });
}

function checkSwitch() {
    var action = "checkSwitch";
    var params = JSON.stringify({});
    var search = window.location.search;
    console.log(search);

    get(url, action, id, params).then(promiseRequest).then(
        function (data) {
            if (!search && (data)) {
                window.location.href = "/?region_id=" + data.region_id;
            }

            if (data) {
                var map_frame = document.getElementById("map_frame");
                map_frame.innerHTML = '<div style="text-align: center;">' + data.region_name + '</div>';
            }

            if (!data && (search)) {
                window.location.href = "/";
            }
        });
}

/* end forces interface*/

window.onload = function() {
    checkSwitch();
    shipInit();
    shipList();
    regionsList();
};
