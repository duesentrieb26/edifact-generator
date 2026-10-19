<?php
namespace EDI\Generator\Westim;

/**
 * One damage of a WESTIM estimate: what is damaged (DAM), how it is repaired (WOR) and what it costs (COS).
 *
 * @package EDI\Generator\Westim
 */
class Damage
{

    /** @var array|null DAM segment, damage */
    private $_damage;
    /** @var array|null WOR segment, repair work */
    private $_work;
    /** @var array|null COS segment, repair cost */
    private $_cost;

    /**
     * Creates an empty damage.
     */
    public function __construct()
    {

    }

    /**
     * Sets the damage (DAM segment).
     *
     * @param string $line                  damage line number, two digits
     * @param string $damageLocationCode    location of the damage on the container
     * @param string $componentCode         damaged component
     * @param string $damageTypeCode        type of damage
     * @param string $componentMaterialCode material of the component
     *
     * @return $this
     */
    public function setDamage($line, $damageLocationCode, $componentCode, $damageTypeCode, $componentMaterialCode)
    {
        $this->_damage = ['DAM', $line, $damageLocationCode, $componentCode, $damageTypeCode, $componentMaterialCode];
        return $this;
    }

    /**
     * Sets the repair work (WOR segment).
     *
     * @param string           $repairMethodCode repair method
     * @param string           $measureUnit      unit of the dimensions
     * @param string|int|float $length           length of the repair
     * @param string|int|float $width            width of the repair
     * @param string|int|float $height           height of the repair
     * @param string|int       $quantity         number of pieces
     *
     * @return $this
     */
    public function setWork($repairMethodCode, $measureUnit, $length, $width, $height, $quantity)
    {
        $this->_work = ['WOR', $repairMethodCode, [$measureUnit, $length, $width, $height], $quantity];
        return $this;
    }

    /**
     * Sets the repair cost (COS segment).
     *
     * @param string|int|float $manHours       labour hours
     * @param string|int|float $materialCost   material cost
     * @param string           $responsibility responsibility code, who pays
     * @param string|int|float $labourRate     labour rate
     *
     * @return $this
     */
    public function setCost($manHours, $materialCost, $responsibility, $labourRate)
    {
        $this->_cost = ['COS', 00, $manHours, $materialCost, $responsibility, $labourRate, 'N'];
        return $this;
    }

    /**
     * Returns the damage segments in message order (DAM, WOR, COS).
     *
     * @return array list of segments
     */
    public function compose()
    {
        $composed = [];
        $composed[] = $this->_damage;
        $composed[] = $this->_work;
        $composed[] = $this->_cost;
        return $composed;
    }
}
