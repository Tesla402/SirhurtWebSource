<?php

function GetRankFromAccessLevel($accesslevel)
{
    if ($accesslevel == 0.1)
    {
        return "Beta Tester";
    }
    
    if ($accesslevel == 0.2)
    {
        return "SirHurt Partner";
    }
    
    if ($accesslevel == 0.3)
    {
        return "Ex-Employee";
    }
    
    if ($accesslevel == 0.4)
    {
        return "Helper";
    }  
    
    if ($accesslevel >= 9)
    {
        return "Head of Operations";
    } 
    
    if ($accesslevel >= 8)
    {
        return "Vice Head of Operations";
    } 
    
    if ($accesslevel >= 7)
    {
        return "Sales Representative";
    } 
    
    if ($accesslevel >= 6)
    {
        return "Operations Manager";
    } 
    
    if ($accesslevel >= 5)
    {
        return "Vice Operations Manager";
    } 
    
    if ($accesslevel == 4)
    {
        return "Developer";
    }   
    
    if ($accesslevel == 3)
    {
        return "Team Lead";
    }    
    
    if ($accesslevel >= 2)
    {
        return "Department Supervisor";
    }      
    
    if ($accesslevel >= 1)
    {
        return "Staff Member";
    }
    
    return "Normal";
}

?>