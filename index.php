<!DOCTYPE html>
<html lang="en">
    
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/style.css">
        <title>Document</title>
    </head>
    
    <body>
        <div class="wrapper">
            <h1>Графік чергування в 5а класі</h1>
            <form method="post" action="index.php">
                <?php
                    session_start();
                    // session_destroy();
                    $months = ["Січень", "Лютий", "Березень", "Квітень", "Травень", "Червень", "Липень", "Серпень", "Вересень", "Жовтень", "Листопад", "Грудень"];
                    $students = [];
                    $currentMonth = date('m');
                    $displayedMonth = ($currentMonth = date('m'));
                    $wholeYear = 0;
                    if (isset($_SESSION["students"])) {
                        $students = json_decode($_SESSION["students"]);
                    }
                    if (isset($_POST["delete_button"])) {
                        array_splice($students, $_POST["delete_button"], 1); // начиная со $studentIndex удалить один элемент, то есть тот что под $studentIndex из массива $students
                        $_SESSION["students"] = json_encode($students);
                        sleep(0.1);
                        header("Location: index.php");
                        exit;
                    }
                    if (isset($_POST["student"])) {
                        $student = $_POST["student"];
                        array_push($students, "$student");
                        $_SESSION["students"] = json_encode($students);
                        sleep(0.1);
                        header("Location: index.php");
                        exit;
                    }
                    if (isset($_POST["tableView"])) {
                        $tableView = $_POST["tableView"];
                        if ($tableView == "currentmonth") {
                            $displayedMonth = ($currentMonth = date('m'));
                            $wholeYear = 0;
                        } else if ($tableView == "nextmonth") {
                            $displayedMonth = ($currentMonth + 1);
                            $wholeYear = 0;
                        } else {
                            $wholeYear = 1;
                        }
                    }
                    echo ("
                        <button type='submit' name='tableView' value='currentmonth'>Поточный місяць</button>
                        <button type='submit' name='tableView' value='nextmonth'>Наступний місяць</button>
                        <button type='submit' name='tableView' value='whole year'>Весь рік</button>
                    ");
                    $_SESSION["students"] = json_encode($students);
                    $duty = [];
                    $actions = ["Підметати","Мити 1-й ряд","Мити 2-й ряд","Мити 3-й ряд"];
                    $daysWeek = ["Неділя","Понеділок","Вівторок","Середа","Четвер","П'ятниця","Субота"];
                    $studentQueueAction = [];
                    $nextActionIndex = 0; // создали следующий индекс действия, начинаем всегда с первого $actions(подметание)
                    for ($i = 0; $i < count($actions); $i++) { // создаем начальные индексы в массиве $studentQueueAction(индекс студента который будет дежурить)
                        $studentQueueAction[$i] = $i; // подряд начиная с первого студента
                    }
                    for ($month = 1; $month <= 12; $month++) { // создание графика для каждого месяца в году
                        $firstWeekDay = date('w', mktime(0, 0, 0, $month, 1, date("Y"))); // определение дня недели 1-го числа каждого месяца
                        $dayWeek = $firstWeekDay; // редервная переменная для изменения дня недели
                        $deysInAMonth = cal_days_in_month(CAL_GREGORIAN, $month, date("Y")); // подсчет количества дней в месяце
                        for ($column = 0; $column < $deysInAMonth; $column++) { // перебираем 31 день чтобы записать в каждый действие на день
                            if ($dayWeek > 6) { // дни недели начинаются с 0(воскресенье), если превышает начинать неделю сначала
                                $dayWeek = 0;
                            }
                            $duty[$column] = []; // на каждый день создаем пустой массив
                            for ($studentIndex = 0; $studentIndex < count($students); $studentIndex++) { // проверяем каждого студента на наличие дежурства
                                $match = 0; // проверка есть ли у студента действие
                                for ($actionIndex = 0; $actionIndex < count($actions); $actionIndex++) { // проверяем какое действие должно быть у студента на каждый день
                                    if ($dayWeek != 0 && $dayWeek != 6) { // проверка на Не выходные
                                        if ($studentQueueAction[$actionIndex] == $studentIndex) { // если совпадает студент с очередью дежурства
                                            $duty[$column][$studentIndex] = $actions[$nextActionIndex]; // добавляем дежурному студенту определенное действие начиная с подметания
                                            $nextActionIndex++; // изменяем индекс на следующее(мойка 1 ряда)
                                            if ($nextActionIndex > (count($actions) - 1)) { // $nextActionIndex не должен превышать количество всех действий
                                                $nextActionIndex = 0;
                                            }
                                            $match = 1; // нашлось действие для студента
                                        } else if ($match < 1) { // не нашлось действие студенту
                                            $duty[$column][$studentIndex] = "-"; // студент отдыхает
                                            
                                        }
                                    } else { // если выходные ставить прочерк всем студентам
                                        $duty[$column][$studentIndex] = "-";
                                    }
                                }
                            }
                            // var_dump($nextActionIndex);
                            if ($dayWeek != 0 && $dayWeek != 6) { // если выходные то перемещаем график на понедельник
                                for ($everyAction = 0; $everyAction < count($studentQueueAction); $everyAction++) { // учет дежурств предыдущего дня
                                    $studentQueueAction[$everyAction] += count($actions); // задействуем следующих 4-х студентов которые не дежурили в предыдущий день
                                    if ($studentQueueAction[$everyAction] >= count($students)) { // если перенос графика на следующий день превышает количество студентов возвращяем действие первому студенту
                                        $studentQueueAction[$everyAction] -= count($students);
                                        if (count($actions) > count($students)) { // если студентов не хватает на все действия переносим лишние действия на следующий день
                                            $difference = (count($actions) - count($students));
                                            $studentQueueAction[$everyAction] -= $difference;
                                        }
                                        $nextActionIndex--; // для каждого перенесшегося на начало студента ставим предыдущее действие, чтоб не начиналось с подметания, а того чего не хватило
                                        if ($nextActionIndex < 0) { // если предыдущее действие меньше нуля возвращаем последнее действие
                                            $nextActionIndex = (count($actions) - 1);
                                        }
                                    }
                                }
                            }
                            $dayWeek++; // следующий день недели
                        }
                        if ($month == $displayedMonth | $wholeYear == 1) {
                            echo ("
                                <h2>" . $months[$month - 1] . "</h2>
                                <div class='table_wrapper'>
                                    <table class='table'>
                                        <thead>
                                            <tr>
                                                <th><strong>Ім'я</strong></th>
                            ");
                                for ($v = $firstWeekDay, $n = 0; $n < $deysInAMonth; $v++, $n++) {
                                    if ($v > 6) {
                                        $v = 0;
                                    }
                                    echo ("<th><strong>" . ($n + 1) . "&nbsp;" . $daysWeek[$v] . "</strong></th>");
                                }
                            echo ("
                                        </tr>
                                    </thead>
                                <tbody>
                            ");
                            for ($studentIndex = 0; $studentIndex < count($students); $studentIndex++) {
                                echo ("<tr>");
                                    echo ("
                                                <td>" . $students[$studentIndex] . "
                                                    <button class='delete_button' type='submit' name='delete_button' value='$studentIndex'>+</button>
                                                </td>
                                    ");
                                    for ($column = 0; $column < $deysInAMonth; $column++) {
                                        echo ("<td ");
                                        if ($duty[$column][$studentIndex] == "-") {
                                            echo ("class='dash'");
                                        }
                                        echo (">" . $duty[$column][$studentIndex] . "</td>");
                                    }
                                echo ("</tr>");
                            }
                            echo ("
                                        </tbody>
                                    </table>
                                </div>
                            ");
                        }
                    }
                    // var_dump($duty);
                    $v = 0;
                    // var_dump($_POST);
                ?>
                <label for="name"><strong>Ім'я</strong></label>
                <input class="text_field" type="text" id="name" name="student">
                <button type="submit">Додати</button>
            </form>
    </div>
</body>

</html>