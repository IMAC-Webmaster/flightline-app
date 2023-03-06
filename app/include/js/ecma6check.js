/*
 * Copyright (c) 2019 Dan Carroll
 *
 * This file is part of FlightLine.
 *
 * FlightLine is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * FlightLine is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FlightLine.  If not, see <https://www.gnu.org/licenses/>.
 */

function checkES6(redirect) {
    if (!isES6Supported()) {
        window.location.href = redirect;
    }
}

function isES6Supported() {
    "use strict";

    if (typeof Symbol == "undefined") return false;
    try {
        eval("class Foo {}");
        eval("var bar = (x) => x+1");
    } catch (e) { return false; }

    return true;
}

//if (checkES6(null)) {
//    // The engine supports ES6 features you want to use
//    var s = document.createElement('script');
//    s.src = "es6script.js";
//    document.head.appendChild(s);
//} else {
//    // The engine doesn't support those ES6 features
//    // Use the boring ES5 :(
//}