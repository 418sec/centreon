<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template match='/'>
<xsl:for-each select='//root'>          
    	<xsl:element name='table'>
			<xsl:attribute name='class'>ListTableSmall</xsl:attribute>
            <xsl:element name='tbody'>
	            <tr class='ListHeader'>
	            	<td><xsl:value-of select='//main/argLabel'/></td>
	            	<td><xsl:value-of select='//main/argValue'/></td>
	            	<td><xsl:value-of select='//main/argExample'/></td>
	            </tr>
	            <xsl:for-each select='//arg'>
	            	<xsl:element name='tr'>                                
	                	<xsl:attribute name='class'><xsl:value-of select='style'/></xsl:attribute>
	                    <xsl:element name='td'><xsl:value-of select='description'/></xsl:element>
	                    <xsl:element name='td'>
	                    	<xsl:element name='input'>
	                        	<xsl:attribute name='type'>text</xsl:attribute>
	                            <xsl:attribute name='value'><xsl:value-of select='value'/></xsl:attribute>
	                            <xsl:attribute name='name'><xsl:value-of select='name'/></xsl:attribute>
	                        </xsl:element>
	                    </xsl:element>
	                    <xsl:element name='td'><xsl:value-of select='example'/></xsl:element>
	                </xsl:element>
	            </xsl:for-each>
			</xsl:element>
		</xsl:element>            
</xsl:for-each>
</xsl:template>
</xsl:stylesheet>